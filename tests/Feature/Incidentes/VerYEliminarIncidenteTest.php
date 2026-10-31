<?php

namespace Tests\Feature\Incidentes;

use App\Incidentes\Dominio\CodigoIncidente;
use App\Incidentes\Dominio\Estado;
use App\Incidentes\Dominio\EventoHistorial;
use App\Incidentes\Models\HistorialIncidente;
use App\Incidentes\Models\Incidente;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class VerYEliminarIncidenteTest extends TestCase
{
    /**
     * Quién puede hacer qué: [rol del actor, el incidente es suyo, operación, código esperado].
     * Lo que no se puede ver responde 404; lo que se ve pero no se puede hacer, 403.
     *
     * @return array<string, array{string, bool, string, int}>
     */
    public static function matrizDePermisos(): array
    {
        return [
            'solicitante ve lo propio' => ['Solicitante', true, 'ver', 200],
            'solicitante edita lo propio' => ['Solicitante', true, 'titulo', 200],
            'solicitante no cambia el estado de lo propio' => ['Solicitante', true, 'estado', 403],
            'solicitante no borra lo propio' => ['Solicitante', true, 'borrar', 403],
            'solicitante ve el historial de lo propio' => ['Solicitante', true, 'historial', 200],

            'solicitante no ve lo ajeno' => ['Solicitante', false, 'ver', 404],
            'solicitante no edita lo ajeno' => ['Solicitante', false, 'titulo', 404],
            'solicitante no cambia el estado de lo ajeno' => ['Solicitante', false, 'estado', 404],
            'solicitante no borra lo ajeno' => ['Solicitante', false, 'borrar', 404],
            'solicitante no ve el historial de lo ajeno' => ['Solicitante', false, 'historial', 404],

            'soporte ve todo' => ['Soporte N1', false, 'ver', 200],
            'soporte edita todo' => ['Soporte N1', false, 'titulo', 200],
            'soporte cambia el estado' => ['Soporte N1', false, 'estado', 200],
            'soporte no borra' => ['Soporte N1', false, 'borrar', 403],
            'soporte ve el historial' => ['Soporte N1', false, 'historial', 200],

            'administrador ve todo' => ['Administrador', false, 'ver', 200],
            'administrador edita todo' => ['Administrador', false, 'titulo', 200],
            'administrador cambia el estado' => ['Administrador', false, 'estado', 200],
            'administrador borra' => ['Administrador', false, 'borrar', 204],
            'administrador ve el historial' => ['Administrador', false, 'historial', 200],
        ];
    }

    #[DataProvider('matrizDePermisos')]
    public function test_matriz_de_permisos_por_rol(string $rol, bool $esPropio, string $operacion, int $esperado): void
    {
        $creador = $this->crearUsuario('Solicitante');
        $actor = $esPropio ? $creador : $this->crearUsuario($rol);
        $incidente = $this->incidenteDe($creador);
        $ruta = "/api/v1/incidentes/{$incidente->id}";
        $http = $this->conToken($this->iniciarSesion($actor));

        $respuesta = match ($operacion) {
            'ver' => $http->getJson($ruta),
            'titulo' => $http->patchJson($ruta, ['titulo' => 'Título nuevo']),
            'estado' => $http->patchJson($ruta, ['estado' => 'en_progreso']),
            'borrar' => $http->deleteJson($ruta),
            'historial' => $http->getJson("$ruta/historial"),
        };

        $respuesta->assertStatus($esperado);
    }

    public function test_el_detalle_incluye_el_codigo_los_estados_siguientes_y_las_personas(): void
    {
        $creador = $this->crearUsuario();
        $agente = $this->crearUsuario('Soporte N1');
        $incidente = $this->incidenteDe($creador, ['usuario_asignado_id' => $agente->id, 'categoria' => 'red']);

        $this->conToken($this->iniciarSesion($creador))->getJson("/api/v1/incidentes/{$incidente->id}")
            ->assertOk()
            ->assertHeader('Content-Type', 'application/json')
            ->assertJsonPath('id', $incidente->id)
            ->assertJsonPath('codigo', CodigoIncidente::deId($incidente->id))
            ->assertJsonPath('categoria', 'red')
            ->assertJsonPath('estados_siguientes', ['en_progreso'])
            ->assertJsonPath('creador', ['id' => $creador->id, 'name' => $creador->name])
            ->assertJsonPath('asignado', ['id' => $agente->id, 'name' => $agente->name]);
    }

    public function test_el_campo_vencido_depende_de_la_fecha_y_del_estado(): void
    {
        $creador = $this->crearUsuario();
        $token = $this->iniciarSesion($creador);

        $vencido = $this->incidenteDe($creador, ['fecha_vencimiento' => now()->subHour()]);
        $enPlazo = $this->incidenteDe($creador, ['fecha_vencimiento' => now()->addHour()]);
        $resuelto = $this->incidenteDe($creador, ['fecha_vencimiento' => now()->subDay(), 'estado' => Estado::Resuelto, 'solucion' => 'Hecho.']);

        $this->conToken($token)->getJson("/api/v1/incidentes/{$vencido->id}")->assertJsonPath('vencido', true);
        $this->conToken($token)->getJson("/api/v1/incidentes/{$enPlazo->id}")->assertJsonPath('vencido', false);
        $this->conToken($token)->getJson("/api/v1/incidentes/{$resuelto->id}")->assertJsonPath('vencido', false);
    }

    public function test_un_incidente_inexistente_o_con_id_invalido_responde_404(): void
    {
        $token = $this->iniciarSesion($this->crearUsuario('Administrador'));

        $this->conToken($token)->getJson('/api/v1/incidentes/999999')->assertNotFound();
        $this->conToken($token)->getJson('/api/v1/incidentes/abc')->assertNotFound();
    }

    public function test_sin_token_responde_401(): void
    {
        $incidente = $this->incidenteDe($this->crearUsuario());

        $this->getJson("/api/v1/incidentes/{$incidente->id}")->assertUnauthorized();
        $this->deleteJson("/api/v1/incidentes/{$incidente->id}")->assertUnauthorized();
    }

    public function test_borrar_oculta_el_incidente_pero_lo_conserva_con_su_historial(): void
    {
        $incidente = $this->incidenteDe($this->crearUsuario());
        $administrador = $this->crearUsuario('Administrador');
        $token = $this->iniciarSesion($administrador);

        $this->conToken($token)->deleteJson("/api/v1/incidentes/{$incidente->id}")->assertNoContent();

        $this->conToken($token)->getJson("/api/v1/incidentes/{$incidente->id}")->assertNotFound();
        $this->conToken($token)->deleteJson("/api/v1/incidentes/{$incidente->id}")->assertNotFound();

        $this->assertNotNull(Incidente::withTrashed()->find($incidente->id)?->deleted_at);

        $entrada = HistorialIncidente::where('incidente_id', $incidente->id)->sole();
        $this->assertSame(EventoHistorial::Borrado, $entrada->evento);
        $this->assertNull($entrada->campo);
        $this->assertSame($administrador->id, $entrada->usuario_id);
    }
}
