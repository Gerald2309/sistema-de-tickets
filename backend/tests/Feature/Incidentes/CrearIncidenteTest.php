<?php

namespace Tests\Feature\Incidentes;

use App\Identidad\Dominio\ClavePermiso;
use App\Incidentes\Dominio\CodigoIncidente;
use App\Incidentes\Dominio\EventoHistorial;
use App\Incidentes\Models\HistorialIncidente;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class CrearIncidenteTest extends TestCase
{
    /**
     * @return array<string, mixed>
     */
    private function datosValidos(): array
    {
        return [
            'titulo' => 'No puedo conectarme a la VPN',
            'descripcion' => 'Desde ayer la VPN rechaza mis credenciales con el error 691.',
            'fecha_vencimiento' => '2030-01-15T10:00:00-05:00',
        ];
    }

    /**
     * @return array<string, array{string, mixed}>
     */
    public static function datosInvalidos(): array
    {
        return [
            'sin título' => ['titulo', null],
            'título de 256 caracteres' => ['titulo', str_repeat('a', 256)],
            'sin descripción' => ['descripcion', null],
            'descripción demasiado larga' => ['descripcion', str_repeat('a', 10001)],
            'prioridad desconocida' => ['prioridad', 'urgentisima'],
            'categoría desconocida' => ['categoria', 'cocina'],
            'sin vencimiento' => ['fecha_vencimiento', null],
            'vencimiento sin zona horaria' => ['fecha_vencimiento', '2030-01-15T10:00:00'],
            'vencimiento solo con fecha' => ['fecha_vencimiento', '2030-01-15'],
            'asignado inexistente' => ['usuario_asignado_id', 999999],
            'asignado que no es un número' => ['usuario_asignado_id', 'abc'],
        ];
    }

    public function test_crea_un_incidente_con_los_datos_minimos(): void
    {
        $solicitante = $this->crearUsuario();
        $token = $this->iniciarSesion($solicitante);

        $respuesta = $this->conToken($token)->postJson('/api/v1/incidentes', $this->datosValidos())
            ->assertCreated()
            ->assertJsonPath('data.estado', 'abierto')
            ->assertJsonPath('data.prioridad', 'media')
            ->assertJsonPath('data.categoria', 'otros')
            ->assertJsonPath('data.estados_siguientes', ['en_progreso'])
            ->assertJsonPath('data.creador.id', $solicitante->id)
            ->assertJsonPath('data.asignado', null)
            ->assertJsonPath('data.solucion', null)
            ->assertJsonPath('data.vencido', false)
            ->assertJsonPath('data.fecha_vencimiento', '2030-01-15T15:00:00Z');

        $this->assertSame(CodigoIncidente::deId($respuesta->json('data.id')), $respuesta->json('data.codigo'));
        $this->assertDatabaseHas('incidentes', ['id' => $respuesta->json('data.id'), 'fecha_vencimiento' => '2030-01-15 15:00:00']);
    }

    public function test_acepta_el_vencimiento_en_utc_con_la_letra_z(): void
    {
        $token = $this->iniciarSesion($this->crearUsuario());

        $this->conToken($token)->postJson('/api/v1/incidentes', ['fecha_vencimiento' => '2030-01-15T20:00:00Z'] + $this->datosValidos())
            ->assertCreated()
            ->assertJsonPath('data.fecha_vencimiento', '2030-01-15T20:00:00Z');
    }

    public function test_acepta_prioridad_y_categoria_cuando_se_indican(): void
    {
        $token = $this->iniciarSesion($this->crearUsuario());

        $this->conToken($token)->postJson('/api/v1/incidentes', ['prioridad' => 'critica', 'categoria' => 'red'] + $this->datosValidos())
            ->assertCreated()
            ->assertJsonPath('data.prioridad', 'critica')
            ->assertJsonPath('data.categoria', 'red');
    }

    public function test_el_creador_y_el_estado_no_se_toman_de_la_peticion(): void
    {
        $solicitante = $this->crearUsuario();
        $otro = $this->crearUsuario();
        $token = $this->iniciarSesion($solicitante);

        $this->conToken($token)->postJson('/api/v1/incidentes', $this->datosValidos() + [
            'usuario_creador_id' => $otro->id,
            'estado' => 'cerrado',
            'id' => 999,
            'solucion' => 'Ya resuelto',
        ])
            ->assertCreated()
            ->assertJsonPath('data.creador.id', $solicitante->id)
            ->assertJsonPath('data.estado', 'abierto')
            ->assertJsonPath('data.solucion', null);
    }

    public function test_deja_la_creacion_en_el_historial(): void
    {
        $solicitante = $this->crearUsuario();
        $token = $this->iniciarSesion($solicitante);

        $id = $this->conToken($token)->postJson('/api/v1/incidentes', $this->datosValidos())->json('data.id');

        $entrada = HistorialIncidente::where('incidente_id', $id)->sole();

        $this->assertSame(EventoHistorial::Creacion, $entrada->evento);
        $this->assertNull($entrada->campo);
        $this->assertSame($solicitante->id, $entrada->usuario_id);
    }

    #[DataProvider('datosInvalidos')]
    public function test_rechaza_datos_invalidos_con_el_error_en_su_campo(string $campo, mixed $valor): void
    {
        $token = $this->iniciarSesion($this->crearUsuario('Soporte N1'));

        $this->conToken($token)->postJson('/api/v1/incidentes', [$campo => $valor] + $this->datosValidos())
            ->assertUnprocessable()
            ->assertHeader('Content-Type', 'application/problem+json')
            ->assertJsonValidationErrors($campo);

        $this->assertDatabaseCount('incidentes', 0);
    }

    public function test_un_asignado_inactivo_no_se_acepta(): void
    {
        $inactivo = $this->crearUsuario('Soporte N1', ['activo' => false]);
        $token = $this->iniciarSesion($this->crearUsuario('Soporte N1'));

        $this->conToken($token)->postJson('/api/v1/incidentes', ['usuario_asignado_id' => $inactivo->id] + $this->datosValidos())
            ->assertUnprocessable()
            ->assertJsonValidationErrors('usuario_asignado_id');
    }

    public function test_asignar_al_crear_exige_poder_reasignar(): void
    {
        $agente = $this->crearUsuario('Soporte N1');

        $solicitante = $this->iniciarSesion($this->crearUsuario('Solicitante'));
        $this->conToken($solicitante)->postJson('/api/v1/incidentes', ['usuario_asignado_id' => $agente->id] + $this->datosValidos())
            ->assertForbidden();

        $soporte = $this->iniciarSesion($this->crearUsuario('Soporte N2'));
        $this->conToken($soporte)->postJson('/api/v1/incidentes', ['usuario_asignado_id' => $agente->id] + $this->datosValidos())
            ->assertCreated()
            ->assertJsonPath('data.asignado.id', $agente->id);

        $this->assertDatabaseCount('incidentes', 1);
    }

    public function test_enviar_el_asignado_en_nulo_no_exige_permiso(): void
    {
        $token = $this->iniciarSesion($this->crearUsuario('Solicitante'));

        $this->conToken($token)->postJson('/api/v1/incidentes', ['usuario_asignado_id' => null] + $this->datosValidos())
            ->assertCreated();
    }

    public function test_sin_el_permiso_de_crear_responde_403(): void
    {
        $token = $this->iniciarSesion($this->usuarioConPermisos(ClavePermiso::VerPropios));

        $this->conToken($token)->postJson('/api/v1/incidentes', $this->datosValidos())->assertForbidden();
    }

    public function test_sin_token_responde_401(): void
    {
        $this->postJson('/api/v1/incidentes', $this->datosValidos())->assertUnauthorized();
    }
}
