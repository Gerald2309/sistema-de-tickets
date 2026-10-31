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
            'solicitante ve lo propio' => ['Solicitante', 'propio', 'ver', 200],
            'solicitante clasifica lo propio sin tomar' => ['Solicitante', 'propio', 'prioridad', 200],
            'solicitante no reescribe el título de lo propio' => ['Solicitante', 'propio', 'titulo', 403],
            'solicitante no clasifica lo propio ya tomado' => ['Solicitante', 'propio tomado', 'prioridad', 403],
            'solicitante no cambia el estado de lo propio' => ['Solicitante', 'propio', 'estado', 403],
            'solicitante no borra lo propio' => ['Solicitante', 'propio', 'borrar', 403],
            'solicitante ve el historial de lo propio' => ['Solicitante', 'propio', 'historial', 200],

            'solicitante no ve lo ajeno' => ['Solicitante', 'libre', 'ver', 404],
            'solicitante no edita lo ajeno' => ['Solicitante', 'libre', 'titulo', 404],
            'solicitante no cambia el estado de lo ajeno' => ['Solicitante', 'libre', 'estado', 404],
            'solicitante no borra lo ajeno' => ['Solicitante', 'libre', 'borrar', 404],
            'solicitante no ve el historial de lo ajeno' => ['Solicitante', 'libre', 'historial', 404],

            'soporte ve todo' => ['Soporte N1', 'libre', 'ver', 200],
            'soporte no edita lo que no ha tomado' => ['Soporte N1', 'libre', 'titulo', 403],
            'soporte no cambia el estado de lo que no ha tomado' => ['Soporte N1', 'libre', 'estado', 403],
            'soporte no edita lo que atiende otra persona' => ['Soporte N1', 'de otro', 'titulo', 403],
            'soporte clasifica lo que atiende' => ['Soporte N1', 'atendido', 'prioridad', 200],
            'soporte no reescribe el título de lo que atiende' => ['Soporte N1', 'atendido', 'titulo', 403],
            'soporte cambia el estado de lo que atiende' => ['Soporte N1', 'atendido', 'estado', 200],
            'soporte no borra' => ['Soporte N1', 'atendido', 'borrar', 403],
            'soporte ve el historial' => ['Soporte N1', 'libre', 'historial', 200],

            'administrador ve todo' => ['Administrador', 'libre', 'ver', 200],
            'administrador no edita lo que no ha tomado' => ['Administrador', 'de otro', 'titulo', 403],
            'administrador no cambia el estado de lo que no ha tomado' => ['Administrador', 'de otro', 'estado', 403],
            'administrador clasifica lo que atiende' => ['Administrador', 'atendido', 'prioridad', 200],
            'administrador no reescribe el título de lo que atiende' => ['Administrador', 'atendido', 'titulo', 403],
            'administrador cambia el estado de lo que atiende' => ['Administrador', 'atendido', 'estado', 200],
            'administrador borra' => ['Administrador', 'libre', 'borrar', 204],
            'administrador ve el historial' => ['Administrador', 'libre', 'historial', 200],
        ];
    }

    /**
     * @param  'propio'|'propio tomado'|'libre'|'atendido'|'de otro'  $relacion  qué es el incidente para quien actúa
     */
    #[DataProvider('matrizDePermisos')]
    public function test_matriz_de_permisos_por_rol(string $rol, string $relacion, string $operacion, int $esperado): void
    {
        $creador = $this->crearUsuario('Solicitante');
        $actor = str_starts_with($relacion, 'propio') ? $creador : $this->crearUsuario($rol);
        $asignado = match ($relacion) {
            'atendido' => $actor,
            'propio tomado', 'de otro' => $this->crearUsuario('Soporte N2'),
            'propio', 'libre' => null,
        };
        $incidente = $this->incidenteDe($creador, ['usuario_asignado_id' => $asignado?->id]);
        $ruta = "/api/v1/incidentes/{$incidente->id}";
        $http = $this->conToken($this->iniciarSesion($actor));

        $respuesta = match ($operacion) {
            'ver' => $http->getJson($ruta),
            'titulo' => $http->patchJson($ruta, ['titulo' => 'Título nuevo']),
            'prioridad' => $http->patchJson($ruta, ['prioridad' => 'alta']),
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
            ->assertJsonPath('data.id', $incidente->id)
            ->assertJsonPath('data.codigo', CodigoIncidente::deId($incidente->id))
            ->assertJsonPath('data.categoria', 'red')
            ->assertJsonPath('data.estados_siguientes', ['en_progreso'])
            ->assertJsonPath('data.creador', ['id' => $creador->id, 'name' => $creador->name])
            ->assertJsonPath('data.asignado', ['id' => $agente->id, 'name' => $agente->name]);
    }

    public function test_el_campo_vencido_depende_de_la_fecha_y_del_estado(): void
    {
        $creador = $this->crearUsuario();
        $token = $this->iniciarSesion($creador);

        $vencido = $this->incidenteDe($creador, ['fecha_vencimiento' => now()->subHour()]);
        $enPlazo = $this->incidenteDe($creador, ['fecha_vencimiento' => now()->addHour()]);
        $resuelto = $this->incidenteDe($creador, ['fecha_vencimiento' => now()->subDay(), 'estado' => Estado::Resuelto, 'solucion' => 'Hecho.']);

        $this->conToken($token)->getJson("/api/v1/incidentes/{$vencido->id}")->assertJsonPath('data.vencido', true);
        $this->conToken($token)->getJson("/api/v1/incidentes/{$enPlazo->id}")->assertJsonPath('data.vencido', false);
        $this->conToken($token)->getJson("/api/v1/incidentes/{$resuelto->id}")->assertJsonPath('data.vencido', false);
    }

    public function test_un_incidente_inexistente_o_con_id_invalido_responde_404(): void
    {
        $token = $this->iniciarSesion($this->crearUsuario('Administrador'));

        $this->conToken($token)->getJson('/api/v1/incidentes/999999')->assertNotFound();
        $this->conToken($token)->getJson('/api/v1/incidentes/abc')->assertNotFound();
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
