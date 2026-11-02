<?php

namespace Tests\Feature\Incidentes;

use App\Identidad\Dominio\ClavePermiso;
use App\Identidad\Models\User;
use App\Incidentes\Dominio\Estado;
use App\Incidentes\Dominio\TipoDeCambio;
use App\Incidentes\Eventos\IncidentesCambiados;
use Illuminate\Broadcasting\Broadcasters\Broadcaster;
use Illuminate\Broadcasting\BroadcastEvent;
use Illuminate\Broadcasting\BroadcastException;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Exceptions;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

// Avisos de tiempo real (F-12): qué incidentes cambiaron, a quién, y que un fallo del WebSocket no tumbe la API.
class TiempoRealTest extends TestCase
{
    private User $soporte;

    private string $token;

    protected function setUp(): void
    {
        parent::setUp();

        $this->prepararDatosBase();
        $this->soporte = $this->usuarioConPermisos(
            ClavePermiso::CrearIncidente, ClavePermiso::VerTodos, ClavePermiso::EditarTodos,
            ClavePermiso::CambiarEstado, ClavePermiso::Reasignar, ClavePermiso::Borrar,
        );
        $this->token = $this->iniciarSesion($this->soporte);
    }

    /**
     * @return list<string>
     */
    private function canales(IncidentesCambiados $evento): array
    {
        return array_map(fn ($canal) => $canal->name, $evento->broadcastOn());
    }

    public function test_crear_avisa_al_canal_general_y_al_creador_sin_datos_del_incidente(): void
    {
        Event::fake([IncidentesCambiados::class]);

        $id = $this->conToken($this->token)->postJson('/api/v1/incidentes', [
            'titulo' => 'Sin internet', 'descripcion' => 'No hay conexión.', 'fecha_vencimiento' => now()->addDay()->toIso8601String(),
        ])->assertCreated()->json('data.id');

        Event::assertDispatchedTimes(IncidentesCambiados::class, 1);
        Event::assertDispatched(IncidentesCambiados::class, function (IncidentesCambiados $evento) use ($id) {
            return $evento->broadcastWith() === ['ids' => [$id], 'tipo' => 'creado']
                && $this->canales($evento) === ['private-incidentes', "private-usuario.{$this->soporte->id}"]
                && $evento->broadcastAs() === 'incidentes.cambiados';
        });
    }

    public function test_reasignar_avisa_al_creador_al_nuevo_asignado_y_al_anterior(): void
    {
        $creador = $this->crearUsuario();
        $anterior = $this->crearUsuario('Soporte N1');
        $nuevo = $this->crearUsuario('Soporte N1');
        $incidente = $this->incidenteDe($creador, ['usuario_asignado_id' => $anterior->id]);
        Event::fake([IncidentesCambiados::class]);

        $this->conToken($this->token)->patchJson("/api/v1/incidentes/{$incidente->id}", ['usuario_asignado_id' => $nuevo->id])->assertOk();

        Event::assertDispatched(IncidentesCambiados::class, fn (IncidentesCambiados $evento) => $evento->tipo === TipoDeCambio::Actualizado
            && $this->canales($evento) === ['private-incidentes', "private-usuario.{$creador->id}", "private-usuario.{$nuevo->id}", "private-usuario.{$anterior->id}"]);
    }

    public function test_un_lote_produce_un_solo_aviso_con_todos_los_ids(): void
    {
        $creador = $this->crearUsuario();
        $ids = collect(range(1, 3))->map(fn () => $this->incidenteDe($creador, ['usuario_asignado_id' => $this->soporte->id])->id)->all();
        Event::fake([IncidentesCambiados::class]);

        $this->conToken($this->token)->patchJson('/api/v1/incidentes/lote', ['ids' => $ids, 'cambios' => ['prioridad' => 'alta']])->assertOk();

        Event::assertDispatchedTimes(IncidentesCambiados::class, 1);
        Event::assertDispatched(IncidentesCambiados::class, fn (IncidentesCambiados $evento) => $evento->ids === $ids);
    }

    public function test_eliminar_avisa_con_el_tipo_eliminado(): void
    {
        $incidente = $this->incidenteDe($this->crearUsuario());
        Event::fake([IncidentesCambiados::class]);

        $this->conToken($this->token)->deleteJson("/api/v1/incidentes/{$incidente->id}")->assertNoContent();

        Event::assertDispatched(IncidentesCambiados::class, fn (IncidentesCambiados $evento) => $evento->tipo === TipoDeCambio::Eliminado);
    }

    public function test_si_nada_cambia_o_la_peticion_falla_no_se_avisa(): void
    {
        $incidente = $this->incidenteAtendidoPor($this->soporte, ['estado' => Estado::Abierto]);
        Event::fake([IncidentesCambiados::class]);

        $this->conToken($this->token)->patchJson("/api/v1/incidentes/{$incidente->id}", ['estado' => 'abierto'])->assertOk();
        $this->conToken($this->token)->patchJson("/api/v1/incidentes/{$incidente->id}", ['estado' => 'cerrado'])->assertUnprocessable();

        Event::assertNotDispatched(IncidentesCambiados::class);
    }

    public function test_el_aviso_va_por_la_cola_y_la_peticion_no_espera_al_servidor_de_websockets(): void
    {
        Queue::fake();

        $this->conToken($this->token)->postJson('/api/v1/incidentes', [
            'titulo' => 'Sin internet', 'descripcion' => 'No hay conexión.',
        ])->assertCreated();

        Queue::assertPushed(BroadcastEvent::class, fn (BroadcastEvent $trabajo) => $trabajo->event instanceof IncidentesCambiados);
    }

    public function test_si_el_servidor_de_websockets_falla_el_cambio_se_guarda_y_el_error_se_registra(): void
    {
        Exceptions::fake();
        Broadcast::extend('caido', fn () => new class extends Broadcaster
        {
            public function auth($request): mixed
            {
                return null;
            }

            public function validAuthenticationResponse($request, $result): mixed
            {
                return null;
            }

            public function broadcast(array $channels, $event, array $payload = []): void
            {
                throw new BroadcastException('Reverb no responde.');
            }
        });
        config(['broadcasting.connections.caido' => ['driver' => 'caido'], 'broadcasting.default' => 'caido']);

        $this->conToken($this->token)->postJson('/api/v1/incidentes', [
            'titulo' => 'Sin internet', 'descripcion' => 'No hay conexión.', 'fecha_vencimiento' => now()->addDay()->toIso8601String(),
        ])->assertCreated();

        $this->assertDatabaseCount('incidentes', 1);
        Exceptions::assertReported(BroadcastException::class);
    }

    public function test_autoriza_cada_canal_solo_a_quien_corresponde(): void
    {
        config([
            'broadcasting.default' => 'reverb',
            'broadcasting.connections.reverb.key' => 'clave-de-prueba',
            'broadcasting.connections.reverb.secret' => 'secreto-de-prueba',
            'broadcasting.connections.reverb.app_id' => '1',
            'broadcasting.connections.reverb.options' => ['host' => 'reverb', 'port' => 8080, 'scheme' => 'http', 'useTLS' => false],
        ]);
        // Los canales se registraron al arrancar sobre el controlador nulo de las pruebas: se registran de nuevo.
        Broadcast::forgetDrivers();
        require base_path('routes/channels.php');

        $solicitante = $this->usuarioConPermisos(ClavePermiso::VerPropios);
        $tokenSolicitante = $this->iniciarSesion($solicitante);
        $autorizar = fn (string $token, string $canal) => $this->conToken($token)
            ->postJson('/api/v1/broadcasting/auth', ['socket_id' => '1234.5678', 'channel_name' => $canal]);

        $autorizar($this->token, 'private-incidentes')->assertOk()->assertJsonStructure(['auth']);
        $autorizar($tokenSolicitante, 'private-incidentes')->assertForbidden();
        $autorizar($tokenSolicitante, "private-usuario.{$solicitante->id}")->assertOk();
        $autorizar($tokenSolicitante, "private-usuario.{$this->soporte->id}")->assertForbidden();
        $this->sinCredencial()->postJson('/api/v1/broadcasting/auth', ['socket_id' => '1234.5678', 'channel_name' => 'private-incidentes'])->assertUnauthorized();
    }
}
