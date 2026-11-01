<?php

namespace Tests\Feature\Incidentes;

use App\Incidentes\Dominio\CampoHistorial;
use App\Incidentes\Dominio\Estado;
use App\Incidentes\Dominio\EventoHistorial;
use App\Incidentes\Models\HistorialIncidente;
use App\Incidentes\Models\Incidente;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ActualizarIncidenteTest extends TestCase
{
    private function ruta(Incidente $incidente): string
    {
        return "/api/v1/incidentes/{$incidente->id}";
    }

    public function test_actualiza_varios_campos_y_registra_cada_cambio_en_el_historial(): void
    {
        $soporte = $this->crearUsuario('Soporte N1');
        $incidente = $this->incidenteDe($soporte, ['usuario_asignado_id' => $soporte->id]);

        $this->conToken($this->iniciarSesion($soporte))->patchJson($this->ruta($incidente), [
            'prioridad' => 'alta',
            'categoria' => 'red',
            'fecha_vencimiento' => '2031-02-01T09:00:00-05:00',
        ])
            ->assertOk()
            ->assertJsonPath('data.prioridad', 'alta')
            ->assertJsonPath('data.categoria', 'red')
            ->assertJsonPath('data.fecha_vencimiento', '2031-02-01T14:00:00Z');

        $entradas = HistorialIncidente::where('incidente_id', $incidente->id)->orderBy('id')->get();

        $this->assertSame(
            [CampoHistorial::Prioridad, CampoHistorial::FechaVencimiento, CampoHistorial::Categoria],
            $entradas->pluck('campo')->all(),
        );
        $this->assertTrue($entradas->every(fn ($entrada) => $entrada->evento === EventoHistorial::Modificacion));
        $this->assertTrue($entradas->every(fn ($entrada) => $entrada->usuario_id === $soporte->id));
        $this->assertSame(['media', 'alta'], [$entradas[0]->valor_anterior, $entradas[0]->valor_nuevo]);
        $this->assertSame('2031-02-01 14:00:00', $entradas[1]->valor_nuevo);
    }

    public function test_cambiar_el_estado_registra_el_paso_y_actualiza_los_siguientes(): void
    {
        $soporte = $this->crearUsuario('Soporte N1');
        $incidente = $this->incidenteAtendidoPor($soporte);
        $token = $this->iniciarSesion($soporte);

        $this->conToken($token)->patchJson($this->ruta($incidente), ['estado' => 'en_progreso'])
            ->assertOk()
            ->assertJsonPath('data.estado', 'en_progreso')
            ->assertJsonPath('data.estados_siguientes', ['resuelto']);

        $entrada = HistorialIncidente::where('incidente_id', $incidente->id)->sole();

        $this->assertSame(CampoHistorial::Estado, $entrada->campo);
        $this->assertSame(['abierto', 'en_progreso'], [$entrada->valor_anterior, $entrada->valor_nuevo]);
    }

    public function test_una_transicion_no_permitida_responde_422_e_indica_los_estados_permitidos(): void
    {
        $soporte = $this->crearUsuario('Soporte N1');
        $incidente = $this->incidenteAtendidoPor($soporte);
        $token = $this->iniciarSesion($soporte);

        $this->conToken($token)->patchJson($this->ruta($incidente), ['estado' => 'resuelto'])
            ->assertUnprocessable()
            ->assertHeader('Content-Type', 'application/problem+json')
            ->assertJsonPath('type', 'urn:incidentes:problema:transicion-invalida')
            ->assertJsonPath('estados_permitidos', ['en_progreso']);

        $this->assertSame(Estado::Abierto, $incidente->fresh()->estado);
        $this->assertSame(0, HistorialIncidente::count());
    }

    public function test_un_incidente_cerrado_no_cambia_de_estado(): void
    {
        $soporte = $this->crearUsuario('Soporte N1');
        $incidente = $this->incidenteAtendidoPor($soporte, ['estado' => Estado::Cerrado, 'solucion' => 'Resuelto hace tiempo.']);
        $token = $this->iniciarSesion($soporte);

        $this->conToken($token)->patchJson($this->ruta($incidente), ['estado' => 'abierto'])
            ->assertUnprocessable()
            ->assertJsonPath('estados_permitidos', [])
            ->assertJsonPath('detail', 'No se puede pasar de «cerrado» a «abierto». Estados permitidos: ninguno (estado final).');
    }

    public function test_resolver_exige_la_solucion(): void
    {
        $soporte = $this->crearUsuario('Soporte N1');
        $incidente = $this->incidenteAtendidoPor($soporte, ['estado' => Estado::EnProgreso]);
        $token = $this->iniciarSesion($soporte);

        $this->conToken($token)->patchJson($this->ruta($incidente), ['estado' => 'resuelto'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('solucion');

        $this->assertSame(Estado::EnProgreso, $incidente->fresh()->estado);

        $this->conToken($token)->patchJson($this->ruta($incidente), ['estado' => 'resuelto', 'solucion' => 'Se reinició el servicio.'])
            ->assertOk()
            ->assertJsonPath('data.estado', 'resuelto')
            ->assertJsonPath('data.solucion', 'Se reinició el servicio.');

        $entradas = HistorialIncidente::where('incidente_id', $incidente->id)->orderBy('id')->get();
        $this->assertSame([CampoHistorial::Estado, CampoHistorial::Solucion], $entradas->pluck('campo')->all());
        $this->assertSame([null, 'Se reinició el servicio.'], [$entradas[1]->valor_anterior, $entradas[1]->valor_nuevo]);
    }

    public function test_quien_lo_reporto_corrige_titulo_y_descripcion_mientras_nadie_lo_ha_tomado_y_queda_en_el_historial(): void
    {
        $solicitante = $this->crearUsuario();
        $incidente = $this->incidenteDe($solicitante, ['titulo' => 'No imprime', 'descripcion' => 'La impresora no responde.']);
        $token = $this->iniciarSesion($solicitante);

        $this->conToken($token)->patchJson($this->ruta($incidente), ['titulo' => 'No imprime en el piso 2', 'descripcion' => 'Sale una hoja en blanco.'])
            ->assertOk()
            ->assertJsonPath('data.titulo', 'No imprime en el piso 2')
            ->assertJsonPath('data.descripcion', 'Sale una hoja en blanco.');
        $this->assertEqualsCanonicalizing(
            [CampoHistorial::Titulo, CampoHistorial::Descripcion],
            HistorialIncidente::pluck('campo')->all(),
        );

        // Una vez tomado, el relato queda fijo: también para quien lo reportó y para quien lo atiende.
        $soporte = $this->crearUsuario('Soporte N1');
        $incidente->update(['usuario_asignado_id' => $soporte->id]);
        $this->conToken($token)->patchJson($this->ruta($incidente), ['titulo' => 'Otro título'])->assertForbidden();
        $this->conToken($this->iniciarSesion($soporte))->patchJson($this->ruta($incidente), ['descripcion' => 'Otro texto'])->assertForbidden();
        $this->assertSame('No imprime en el piso 2', $incidente->fresh()->titulo);
    }

    /**
     * @return array<string, array{Estado, array<string, string>}>
     */
    public static function peticionesConSolucionSinResolver(): array
    {
        return [
            'solo la solución' => [Estado::EnProgreso, ['solucion' => 'Se reinició.']],
            'con otro estado de destino' => [Estado::Abierto, ['estado' => 'en_progreso', 'solucion' => 'Se reinició.']],
            'resuelto que pide resolver otra vez (no es una transición)' => [Estado::Resuelto, ['estado' => 'resuelto', 'solucion' => 'Otra solución.']],
            'cerrado' => [Estado::Cerrado, ['solucion' => 'Otra solución.']],
        ];
    }

    /**
     * @param  array<string, string>  $cuerpo
     */
    #[DataProvider('peticionesConSolucionSinResolver')]
    public function test_la_solucion_solo_se_acepta_al_resolver(Estado $estadoActual, array $cuerpo): void
    {
        $soporte = $this->crearUsuario('Soporte N1');
        $incidente = $this->incidenteAtendidoPor($soporte, [
            'estado' => $estadoActual,
            'solucion' => in_array($estadoActual, [Estado::Resuelto, Estado::Cerrado], true) ? 'Solución original.' : null,
        ]);
        $token = $this->iniciarSesion($soporte);

        $this->conToken($token)->patchJson($this->ruta($incidente), $cuerpo)
            ->assertUnprocessable()
            ->assertJsonPath('errors.solucion', ['La solución solo se registra al resolver el incidente.']);

        $this->assertSame($incidente->solucion, $incidente->fresh()->solucion, 'La solución no cambia.');
        $this->assertSame($estadoActual, $incidente->fresh()->estado);
    }

    public function test_al_resolver_otra_vez_la_nueva_solucion_reemplaza_a_la_anterior(): void
    {
        $soporte = $this->crearUsuario('Soporte N1');
        $incidente = $this->incidenteAtendidoPor($soporte, ['estado' => Estado::EnProgreso, 'solucion' => 'Primera solución.']);
        $token = $this->iniciarSesion($soporte);

        $this->conToken($token)->patchJson($this->ruta($incidente), ['estado' => 'resuelto', 'solucion' => 'Solución definitiva.'])
            ->assertOk()
            ->assertJsonPath('data.solucion', 'Solución definitiva.');
    }

    public function test_una_transicion_invalida_se_informa_antes_que_el_problema_de_la_solucion(): void
    {
        $soporte = $this->crearUsuario('Soporte N1');
        $incidente = $this->incidenteAtendidoPor($soporte);
        $token = $this->iniciarSesion($soporte);

        $this->conToken($token)->patchJson($this->ruta($incidente), ['estado' => 'resuelto', 'solucion' => 'Se reinició.'])
            ->assertUnprocessable()
            ->assertJsonPath('type', 'urn:incidentes:problema:transicion-invalida');
    }

    public function test_al_reabrir_se_conserva_la_solucion_y_al_resolver_de_nuevo_no_hace_falta_otra(): void
    {
        $soporte = $this->crearUsuario('Soporte N1');
        $incidente = $this->incidenteAtendidoPor($soporte, ['estado' => Estado::Resuelto, 'solucion' => 'Primera solución.']);
        $token = $this->iniciarSesion($soporte);

        $this->conToken($token)->patchJson($this->ruta($incidente), ['estado' => 'en_progreso'])
            ->assertOk()
            ->assertJsonPath('data.solucion', 'Primera solución.');

        $this->conToken($token)->patchJson($this->ruta($incidente), ['estado' => 'resuelto'])
            ->assertOk()
            ->assertJsonPath('data.solucion', 'Primera solución.');
    }

    public function test_resolver_anota_cuando_reabrir_lo_borra_y_cerrar_lo_conserva(): void
    {
        $this->freezeSecond();
        $soporte = $this->crearUsuario('Soporte N1');
        $incidente = $this->incidenteAtendidoPor($soporte, ['estado' => Estado::EnProgreso]);
        $token = $this->iniciarSesion($soporte);

        $this->conToken($token)->patchJson($this->ruta($incidente), ['estado' => 'resuelto', 'solucion' => 'Listo.'])->assertOk();
        $this->assertEquals(now(), $incidente->refresh()->resuelto_en);

        $this->conToken($token)->patchJson($this->ruta($incidente), ['estado' => 'en_progreso'])->assertOk();
        $this->assertNull($incidente->refresh()->resuelto_en);

        $this->travel(2)->minutes();
        $this->conToken($token)->patchJson($this->ruta($incidente), ['estado' => 'resuelto'])->assertOk();
        $this->travel(3)->minutes();
        $this->conToken($token)->patchJson($this->ruta($incidente), ['estado' => 'cerrado'])->assertOk();

        $this->assertEquals(now()->subMinutes(3), $incidente->refresh()->resuelto_en);
    }

    public function test_pedir_el_estado_actual_no_cambia_nada_ni_deja_historial(): void
    {
        $soporte = $this->crearUsuario('Soporte N1');
        $incidente = $this->incidenteAtendidoPor($soporte);
        $token = $this->iniciarSesion($soporte);

        $this->conToken($token)->patchJson($this->ruta($incidente), ['estado' => 'abierto'])->assertOk();

        $this->assertSame(0, HistorialIncidente::count());
    }

    public function test_repetir_un_valor_igual_no_deja_historial(): void
    {
        $soporte = $this->crearUsuario('Soporte N1');
        $incidente = $this->incidenteDe($soporte, ['categoria' => 'red', 'usuario_asignado_id' => $soporte->id]);
        $token = $this->iniciarSesion($soporte);

        $this->conToken($token)->patchJson($this->ruta($incidente), ['categoria' => 'red', 'prioridad' => 'media'])->assertOk();

        $this->assertSame(0, HistorialIncidente::count());
    }

    public function test_reasignar_y_desasignar_se_registran_con_el_id_como_texto(): void
    {
        $agente = $this->crearUsuario('Soporte N1');
        $incidente = $this->incidenteDe($this->crearUsuario());
        $token = $this->iniciarSesion($this->crearUsuario('Administrador'));

        $this->conToken($token)->patchJson($this->ruta($incidente), ['usuario_asignado_id' => $agente->id])
            ->assertOk()
            ->assertJsonPath('data.asignado.id', $agente->id);

        $this->conToken($token)->patchJson($this->ruta($incidente), ['usuario_asignado_id' => null])
            ->assertOk()
            ->assertJsonPath('data.asignado', null);

        $entradas = HistorialIncidente::where('incidente_id', $incidente->id)->orderBy('id')->get();

        $this->assertSame([null, (string) $agente->id], [$entradas[0]->valor_anterior, $entradas[0]->valor_nuevo]);
        $this->assertSame([(string) $agente->id, null], [$entradas[1]->valor_anterior, $entradas[1]->valor_nuevo]);
        $this->assertSame(CampoHistorial::UsuarioAsignado, $entradas[1]->campo);
    }

    public function test_quien_lo_atiende_lo_pasa_a_otra_persona_pero_no_lo_deja_sin_asignar(): void
    {
        $soporte = $this->crearUsuario('Soporte N1');
        $escalado = $this->crearUsuario('Soporte N2');
        $incidente = $this->incidenteAtendidoPor($soporte);
        $token = $this->iniciarSesion($soporte);

        $this->conToken($token)->patchJson($this->ruta($incidente), ['usuario_asignado_id' => null])->assertForbidden();
        $this->assertSame($soporte->id, $incidente->fresh()->usuario_asignado_id);

        $this->conToken($token)->patchJson($this->ruta($incidente), ['usuario_asignado_id' => $escalado->id])
            ->assertOk()
            ->assertJsonPath('data.asignado.id', $escalado->id);

        // Ya no lo atiende: deja de poder trabajarlo.
        $this->conToken($token)->patchJson($this->ruta($incidente), ['prioridad' => 'alta'])->assertForbidden();
    }

    public function test_no_se_asigna_a_un_usuario_inactivo(): void
    {
        $inactivo = $this->crearUsuario('Soporte N1', ['activo' => false]);
        $soporte = $this->crearUsuario('Soporte N2');
        $incidente = $this->incidenteAtendidoPor($soporte);
        $token = $this->iniciarSesion($soporte);

        $this->conToken($token)->patchJson($this->ruta($incidente), ['usuario_asignado_id' => $inactivo->id])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('usuario_asignado_id');
    }

    public function test_solo_se_asigna_a_quien_atiende_incidentes_ni_siquiera_el_administrador_a_un_solicitante(): void
    {
        $solicitante = $this->crearUsuario('Solicitante');
        $administrador = $this->crearUsuario('Administrador');
        $soporte = $this->crearUsuario('Soporte N1');
        $incidente = $this->incidenteAtendidoPor($soporte);

        foreach ([$soporte, $administrador] as $quien) {
            $this->conToken($this->iniciarSesion($quien))->patchJson($this->ruta($incidente), ['usuario_asignado_id' => $solicitante->id])
                ->assertUnprocessable()
                ->assertJsonValidationErrors(['usuario_asignado_id' => 'atiende incidentes']);
        }

        $this->conToken($this->iniciarSesion($soporte))->patchJson($this->ruta($incidente), ['usuario_asignado_id' => $administrador->id])->assertOk();
    }

    public function test_el_creador_clasifica_su_incidente_pero_no_el_estado_ni_el_asignado(): void
    {
        $creador = $this->crearUsuario();
        $incidente = $this->incidenteDe($creador);
        $token = $this->iniciarSesion($creador);

        $this->conToken($token)->patchJson($this->ruta($incidente), ['prioridad' => 'alta'])
            ->assertOk()
            ->assertJsonPath('data.prioridad', 'alta');

        $this->conToken($token)->patchJson($this->ruta($incidente), ['estado' => 'en_progreso'])->assertForbidden();
        $this->conToken($token)->patchJson($this->ruta($incidente), ['usuario_asignado_id' => $this->crearUsuario('Soporte N1')->id])->assertForbidden();
    }

    public function test_un_solo_campo_sin_permiso_rechaza_toda_la_peticion(): void
    {
        $creador = $this->crearUsuario();
        $incidente = $this->incidenteDe($creador);
        $token = $this->iniciarSesion($creador);

        $this->conToken($token)->patchJson($this->ruta($incidente), ['prioridad' => 'alta', 'estado' => 'en_progreso'])
            ->assertForbidden();

        $this->assertSame(0, HistorialIncidente::count());
    }

    public function test_sobre_un_incidente_ajeno_responde_404_aunque_el_cuerpo_sea_invalido(): void
    {
        $incidente = $this->incidenteDe($this->crearUsuario());
        $token = $this->iniciarSesion($this->crearUsuario());

        $this->conToken($token)->patchJson($this->ruta($incidente), ['prioridad' => 'inexistente'])->assertNotFound();
    }

    public function test_el_cuerpo_invalido_responde_422_cuando_si_tiene_permiso(): void
    {
        $soporte = $this->crearUsuario('Soporte N1');
        $incidente = $this->incidenteAtendidoPor($soporte);
        $token = $this->iniciarSesion($soporte);

        $this->conToken($token)->patchJson($this->ruta($incidente), ['prioridad' => 'inexistente', 'estado' => 'volando'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['prioridad', 'estado']);
    }

    public function test_el_creador_y_el_id_no_se_pueden_modificar(): void
    {
        $creador = $this->crearUsuario();
        $soporte = $this->crearUsuario('Soporte N1');
        $incidente = $this->incidenteDe($creador, ['usuario_asignado_id' => $soporte->id]);
        $token = $this->iniciarSesion($soporte);

        $this->conToken($token)->patchJson($this->ruta($incidente), ['usuario_creador_id' => $this->crearUsuario()->id, 'id' => 9999, 'prioridad' => 'alta'])
            ->assertOk()
            ->assertJsonPath('data.id', $incidente->id)
            ->assertJsonPath('data.creador.id', $creador->id);
    }

    public function test_un_incidente_borrado_responde_404(): void
    {
        $incidente = $this->incidenteDe($this->crearUsuario());
        $incidente->delete();
        $token = $this->iniciarSesion($this->crearUsuario('Administrador'));

        $this->conToken($token)->patchJson($this->ruta($incidente), ['titulo' => 'Nuevo'])->assertNotFound();
    }
}
