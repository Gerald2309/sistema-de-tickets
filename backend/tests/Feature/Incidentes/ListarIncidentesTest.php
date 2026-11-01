<?php

namespace Tests\Feature\Incidentes;

use App\Identidad\Dominio\ClavePermiso;
use App\Identidad\Models\User;
use App\Incidentes\Dominio\Categoria;
use App\Incidentes\Dominio\Estado;
use App\Incidentes\Dominio\Prioridad;
use App\Incidentes\Models\Incidente;
use App\Incidentes\Policies\IncidentePolicy;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Testing\TestResponse;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ListarIncidentesTest extends TestCase
{
    /**
     * @param  array<string, mixed>  $consulta
     */
    private function listar(string $token, array $consulta = []): TestResponse
    {
        return $this->conToken($token)->getJson('/api/v1/incidentes'.($consulta ? '?'.http_build_query($consulta) : ''));
    }

    /**
     * @return list<int>
     */
    private function ids(TestResponse $respuesta): array
    {
        return array_column($respuesta->json('data'), 'id');
    }

    /**
     * @return array<string, array{array<string, mixed>}>
     */
    public static function consultasInvalidas(): array
    {
        return [
            'prioridad desconocida' => [['prioridad' => 'urgentisima']],
            'estado desconocido' => [['estado' => 'volando']],
            'categoría desconocida' => [['categoria' => 'cocina']],
            'asignado que no existe' => [['usuario_asignado_id' => 999999]],
            'asignado que no es un número' => [['usuario_asignado_id' => 'abc']],
            'campo de fecha desconocido' => [['campo_fecha' => 'actualizacion']],
            'orden por título' => [['ordenar_por' => 'titulo']],
            'orden por un campo que no existe' => [['ordenar_por' => 'contrasena']],
            'dirección desconocida' => [['direccion' => 'arriba']],
            'fecha sin zona horaria' => [['fecha_desde' => '2030-01-15T10:00:00']],
            'fecha con solo el día' => [['fecha_hasta' => '2030-01-15']],
            'página cero' => [['page' => 0]],
            'página que no es un número' => [['page' => 'segunda']],
            'cero resultados por página' => [['per_page' => 0]],
            'más de 100 resultados por página' => [['per_page' => 101]],
            'resultados por página que no es un número' => [['per_page' => 'muchos']],
            'búsqueda demasiado larga' => [['q' => str_repeat('a', 101)]],
        ];
    }

    #[DataProvider('consultasInvalidas')]
    public function test_rechaza_parametros_invalidos_con_el_error_en_su_campo(array $consulta): void
    {
        $token = $this->iniciarSesion($this->crearUsuario('Soporte N1'));

        $this->listar($token, $consulta)
            ->assertUnprocessable()
            ->assertHeader('Content-Type', 'application/problem+json')
            ->assertJsonValidationErrors(array_key_first($consulta));
    }

    public function test_cada_quien_ve_solo_lo_que_le_corresponde(): void
    {
        $ana = $this->crearUsuario();
        $luis = $this->crearUsuario();
        $propiosDeAna = [$this->incidenteDe($ana)->id, $this->incidenteDe($ana)->id, $this->incidenteDe($ana)->id];
        $propiosDeLuis = [$this->incidenteDe($luis)->id, $this->incidenteDe($luis)->id];
        $this->incidenteDe($ana)->delete();

        $this->assertEqualsCanonicalizing($propiosDeAna, $this->ids($this->listar($this->iniciarSesion($ana))));
        $this->assertEqualsCanonicalizing($propiosDeLuis, $this->ids($this->listar($this->iniciarSesion($luis))));

        $todos = [...$propiosDeAna, ...$propiosDeLuis];
        $this->assertEqualsCanonicalizing($todos, $this->ids($this->listar($this->iniciarSesion($this->crearUsuario('Soporte N1')))));
        $this->assertEqualsCanonicalizing($todos, $this->ids($this->listar($this->iniciarSesion($this->crearUsuario('Administrador')))));
    }

    public function test_el_listado_no_lleva_los_textos_largos_y_el_detalle_si(): void
    {
        $soporte = $this->crearUsuario('Soporte N1');
        $incidente = $this->incidenteDe($soporte, ['descripcion' => str_repeat('Texto largo. ', 500), 'solucion' => 'Se reinició.']);
        $token = $this->iniciarSesion($soporte);

        $fila = $this->listar($token)->assertOk()->json('data.0');
        $this->assertArrayNotHasKey('descripcion', $fila);
        $this->assertArrayNotHasKey('solucion', $fila);
        $this->assertSame($incidente->titulo, $fila['titulo']);

        $this->conToken($token)->getJson("/api/v1/incidentes/{$incidente->id}")
            ->assertJsonPath('data.descripcion', str_repeat('Texto largo. ', 500))
            ->assertJsonPath('data.solucion', 'Se reinició.');
    }

    public function test_sin_permiso_para_ver_responde_403(): void
    {
        $token = $this->iniciarSesion($this->usuarioConPermisos(ClavePermiso::CrearIncidente));

        $this->listar($token)->assertForbidden();
    }

    public function test_el_listado_coincide_con_lo_que_la_policy_deja_ver(): void
    {
        $creadores = [$this->crearUsuario(), $this->crearUsuario(), $this->crearUsuario('Soporte N1')];
        $incidentes = array_map(fn (User $creador) => $this->incidenteDe($creador), [...$creadores, ...$creadores]);

        $actores = [
            'solicitante' => $creadores[0],
            'soporte' => $creadores[2],
            'administrador' => $this->crearUsuario('Administrador'),
            'solo ver propios' => $this->usuarioConPermisos(ClavePermiso::VerPropios),
            'sin permisos de lectura' => $this->usuarioConPermisos(ClavePermiso::CrearIncidente),
        ];

        foreach ($actores as $nombre => $actor) {
            $enElListado = Incidente::visiblePara(IncidentePolicy::alcanceDe($actor))->pluck('id')->all();
            $quePermiteLaPolicy = collect($incidentes)
                ->filter(fn (Incidente $incidente) => Gate::forUser($actor)->allows('ver', $incidente))
                ->pluck('id')
                ->all();

            $this->assertEqualsCanonicalizing($quePermiteLaPolicy, $enElListado, "El listado y la Policy discrepan para: $nombre.");
        }
    }

    public function test_pagina_de_15_en_15_con_el_total_y_los_enlaces(): void
    {
        $creador = $this->crearUsuario();
        collect(range(1, 20))->each(fn () => $this->incidenteDe($creador));
        $token = $this->iniciarSesion($creador);

        $this->listar($token)
            ->assertOk()
            ->assertJsonCount(15, 'data')
            ->assertJsonPath('meta.total', 20)
            ->assertJsonPath('meta.per_page', 15)
            ->assertJsonPath('meta.current_page', 1)
            ->assertJsonPath('meta.last_page', 2);

        $this->listar($token, ['page' => 2])->assertJsonCount(5, 'data')->assertJsonPath('meta.current_page', 2);

        $respuesta = $this->listar($token, ['per_page' => 5])->assertJsonCount(5, 'data')->assertJsonPath('meta.last_page', 4);
        $this->assertStringContainsString('per_page=5', $respuesta->json('links.next'), 'Los enlaces conservan los filtros.');
    }

    public function test_cada_elemento_trae_los_mismos_datos_que_el_detalle_salvo_los_textos_largos_y_los_plazos(): void
    {
        $creador = $this->crearUsuario();
        $incidente = $this->incidenteDe($creador);

        $listado = $this->listar($this->iniciarSesion($creador))->json('data.0');
        $detalle = $this->conToken($this->iniciarSesion($creador))->getJson("/api/v1/incidentes/{$incidente->id}")->json('data');

        $this->assertSame(array_diff_key($detalle, array_flip(['descripcion', 'solucion', 'vence_respuesta_en', 'primera_respuesta_en', 'resuelto_en'])), $listado);
    }

    public function test_ordena_por_id_descendente_por_defecto(): void
    {
        $creador = $this->crearUsuario();
        $ids = [$this->incidenteDe($creador)->id, $this->incidenteDe($creador)->id, $this->incidenteDe($creador)->id];

        $this->assertSame(array_reverse($ids), $this->ids($this->listar($this->iniciarSesion($creador))));
        $this->assertSame($ids, $this->ids($this->listar($this->iniciarSesion($creador), ['direccion' => 'asc'])));
    }

    public function test_ordena_la_prioridad_y_el_estado_por_su_orden_logico_y_no_alfabetico(): void
    {
        $creador = $this->crearUsuario();
        $critica = $this->incidenteDe($creador, ['prioridad' => Prioridad::Critica, 'estado' => Estado::Cerrado, 'solucion' => 'Hecho.']);
        $baja = $this->incidenteDe($creador, ['prioridad' => Prioridad::Baja, 'estado' => Estado::Abierto]);
        $alta = $this->incidenteDe($creador, ['prioridad' => Prioridad::Alta, 'estado' => Estado::Resuelto, 'solucion' => 'Hecho.']);
        $media = $this->incidenteDe($creador, ['prioridad' => Prioridad::Media, 'estado' => Estado::EnProgreso]);
        $token = $this->iniciarSesion($creador);

        $this->assertSame([$baja->id, $media->id, $alta->id, $critica->id], $this->ids($this->listar($token, ['ordenar_por' => 'prioridad', 'direccion' => 'asc'])));
        $this->assertSame([$critica->id, $alta->id, $media->id, $baja->id], $this->ids($this->listar($token, ['ordenar_por' => 'prioridad', 'direccion' => 'desc'])));
        $this->assertSame([$baja->id, $media->id, $alta->id, $critica->id], $this->ids($this->listar($token, ['ordenar_por' => 'estado', 'direccion' => 'asc'])));
    }

    public function test_ordena_por_vencimiento_y_por_fecha_de_creacion(): void
    {
        $creador = $this->crearUsuario();
        $tarde = $this->incidenteDe($creador, ['fecha_vencimiento' => '2030-03-01 10:00:00', 'created_at' => '2030-01-01 10:00:00']);
        $pronto = $this->incidenteDe($creador, ['fecha_vencimiento' => '2030-01-01 10:00:00', 'created_at' => '2030-02-01 10:00:00']);
        $token = $this->iniciarSesion($creador);

        $this->assertSame([$pronto->id, $tarde->id], $this->ids($this->listar($token, ['ordenar_por' => 'fecha_vencimiento', 'direccion' => 'asc'])));
        $this->assertSame([$tarde->id, $pronto->id], $this->ids($this->listar($token, ['ordenar_por' => 'creado', 'direccion' => 'asc'])));
    }

    public function test_el_empate_se_resuelve_por_id_en_el_mismo_sentido_del_orden(): void
    {
        $creador = $this->crearUsuario();
        $ids = collect(range(1, 4))->map(fn () => $this->incidenteDe($creador, ['prioridad' => Prioridad::Alta])->id)->all();
        $token = $this->iniciarSesion($creador);

        $this->assertSame($ids, $this->ids($this->listar($token, ['ordenar_por' => 'prioridad', 'direccion' => 'asc'])));
        $this->assertSame(array_reverse($ids), $this->ids($this->listar($token, ['ordenar_por' => 'prioridad', 'direccion' => 'desc'])));
    }

    public function test_filtra_por_prioridad_estado_categoria_y_asignado(): void
    {
        $creador = $this->crearUsuario();
        $agente = $this->crearUsuario('Soporte N1');
        $objetivo = $this->incidenteDe($creador, [
            'prioridad' => Prioridad::Alta,
            'estado' => Estado::EnProgreso,
            'categoria' => Categoria::Red,
            'usuario_asignado_id' => $agente->id,
        ]);
        $this->incidenteDe($creador, ['prioridad' => Prioridad::Alta, 'estado' => Estado::Abierto, 'categoria' => Categoria::Red, 'usuario_asignado_id' => $agente->id]);
        $this->incidenteDe($creador, ['prioridad' => Prioridad::Baja, 'estado' => Estado::EnProgreso, 'categoria' => Categoria::Red, 'usuario_asignado_id' => $agente->id]);
        $this->incidenteDe($creador, ['prioridad' => Prioridad::Alta, 'estado' => Estado::EnProgreso, 'categoria' => Categoria::Correo, 'usuario_asignado_id' => $agente->id]);
        $this->incidenteDe($creador, ['prioridad' => Prioridad::Alta, 'estado' => Estado::EnProgreso, 'categoria' => Categoria::Red]);
        $token = $this->iniciarSesion($this->crearUsuario('Soporte N2'));

        $this->assertCount(4, $this->ids($this->listar($token, ['prioridad' => 'alta'])), 'Cuatro incidentes son de prioridad alta.');
        $this->assertCount(4, $this->ids($this->listar($token, ['estado' => 'en_progreso'])));
        $this->assertCount(4, $this->ids($this->listar($token, ['categoria' => 'red'])));
        $this->assertCount(4, $this->ids($this->listar($token, ['usuario_asignado_id' => $agente->id])));

        $this->assertSame(
            [$objetivo->id],
            $this->ids($this->listar($token, ['prioridad' => 'alta', 'estado' => 'en_progreso', 'categoria' => 'red', 'usuario_asignado_id' => $agente->id])),
            'Los filtros se combinan: se exigen todos.',
        );
    }

    public function test_un_filtro_sin_coincidencias_devuelve_una_lista_vacia(): void
    {
        $creador = $this->crearUsuario();
        $this->incidenteDe($creador);

        $this->listar($this->iniciarSesion($creador), ['prioridad' => 'critica'])
            ->assertOk()
            ->assertJsonCount(0, 'data')
            ->assertJsonPath('meta.total', 0);
    }

    public function test_el_rango_de_fechas_incluye_los_limites_y_por_defecto_es_sobre_el_vencimiento(): void
    {
        $creador = $this->crearUsuario();
        $primero = $this->incidenteDe($creador, ['fecha_vencimiento' => '2030-01-10 12:00:00']);
        $medio = $this->incidenteDe($creador, ['fecha_vencimiento' => '2030-01-15 12:00:00']);
        $ultimo = $this->incidenteDe($creador, ['fecha_vencimiento' => '2030-01-20 12:00:00']);
        $token = $this->iniciarSesion($creador);

        $this->assertSame([$medio->id], $this->ids($this->listar($token, ['fecha_desde' => '2030-01-15T12:00:00Z', 'fecha_hasta' => '2030-01-15T12:00:00Z'])));
        $this->assertEqualsCanonicalizing([$medio->id, $ultimo->id], $this->ids($this->listar($token, ['fecha_desde' => '2030-01-15T12:00:00Z'])));
        $this->assertEqualsCanonicalizing([$primero->id, $medio->id], $this->ids($this->listar($token, ['fecha_hasta' => '2030-01-15T12:00:00Z'])));
    }

    public function test_los_limites_con_otra_zona_horaria_se_convierten_a_utc(): void
    {
        $creador = $this->crearUsuario();
        $incidente = $this->incidenteDe($creador, ['fecha_vencimiento' => '2030-01-15 17:00:00']);
        $token = $this->iniciarSesion($creador);

        // 12:00 en -05:00 es 17:00 en UTC.
        $this->assertSame([$incidente->id], $this->ids($this->listar($token, ['fecha_desde' => '2030-01-15T12:00:00-05:00', 'fecha_hasta' => '2030-01-15T12:00:00-05:00'])));
        $this->assertSame([], $this->ids($this->listar($token, ['fecha_desde' => '2030-01-15T12:00:01-05:00'])));
    }

    public function test_el_rango_puede_aplicarse_a_la_fecha_de_creacion(): void
    {
        $creador = $this->crearUsuario();
        $antiguo = $this->incidenteDe($creador, ['created_at' => '2030-01-01 08:00:00', 'fecha_vencimiento' => '2030-06-01 08:00:00']);
        $reciente = $this->incidenteDe($creador, ['created_at' => '2030-02-01 08:00:00', 'fecha_vencimiento' => '2030-03-01 08:00:00']);
        $token = $this->iniciarSesion($creador);

        $porCreacion = $this->listar($token, ['campo_fecha' => 'creacion', 'fecha_desde' => '2030-01-15T00:00:00Z']);
        $porVencimiento = $this->listar($token, ['campo_fecha' => 'vencimiento', 'fecha_desde' => '2030-05-01T00:00:00Z']);

        $this->assertSame([$reciente->id], $this->ids($porCreacion));
        $this->assertSame([$antiguo->id], $this->ids($porVencimiento));
    }

    public function test_un_rango_con_el_inicio_posterior_al_fin_se_rechaza(): void
    {
        $token = $this->iniciarSesion($this->crearUsuario('Soporte N1'));

        $this->listar($token, ['fecha_desde' => '2030-02-01T00:00:00Z', 'fecha_hasta' => '2030-01-01T00:00:00Z'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('fecha_hasta');
    }

    public function test_la_cantidad_de_consultas_no_depende_del_tamano_de_la_pagina(): void
    {
        $creador = $this->crearUsuario();
        $agente = $this->crearUsuario('Soporte N1');
        collect(range(1, 30))->each(fn (int $n) => $this->incidenteDe($creador, ['usuario_asignado_id' => $n % 2 ? $agente->id : null]));
        $token = $this->iniciarSesion($this->crearUsuario('Soporte N2'));

        // Sanctum actualiza la fecha de último uso del token cuando cambia el segundo: con el reloj
        // quieto y una petición previa, esa consulta no entra en la comparación.
        $this->freezeTime();
        $this->listar($token)->assertOk();

        $consultas = function (int $porPagina) use ($token): int {
            DB::flushQueryLog();
            DB::enableQueryLog();
            $this->listar($token, ['per_page' => $porPagina])->assertOk()->assertJsonCount($porPagina, 'data');

            return count(DB::getQueryLog());
        };

        $conCinco = $consultas(5);
        $conTreinta = $consultas(30);

        $this->assertSame($conCinco, $conTreinta, 'Más filas por página no debe significar más consultas (N+1).');
    }

    public function test_el_filtro_se_combina_con_la_visibilidad(): void
    {
        $ana = $this->crearUsuario();
        $luis = $this->crearUsuario();
        $deAna = $this->incidenteDe($ana, ['prioridad' => Prioridad::Alta]);
        $this->incidenteDe($luis, ['prioridad' => Prioridad::Alta]);

        $this->assertSame([$deAna->id], $this->ids($this->listar($this->iniciarSesion($ana), ['prioridad' => 'alta'])));
    }

    public function test_una_peticion_de_otra_pagina_fuera_de_rango_devuelve_una_lista_vacia(): void
    {
        $creador = $this->crearUsuario();
        $this->incidenteDe($creador);

        $this->listar($this->iniciarSesion($creador), ['page' => 9])->assertOk()->assertJsonCount(0, 'data');
    }

    public function test_los_vencimientos_en_otra_zona_se_devuelven_siempre_en_utc(): void
    {
        $creador = $this->crearUsuario();
        $this->incidenteDe($creador, ['fecha_vencimiento' => Carbon::parse('2030-01-15T10:00:00-05:00')->utc()]);

        $this->listar($this->iniciarSesion($creador))->assertJsonPath('data.0.fecha_vencimiento', '2030-01-15T15:00:00Z');
    }
}
