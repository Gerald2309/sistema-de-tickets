<?php

namespace Tests\Feature\Incidentes;

use App\Identidad\Models\User;
use App\Incidentes\Acciones\TraspasarIncidentesAbiertos;
use App\Incidentes\Dominio\CampoHistorial;
use App\Incidentes\Dominio\Estado;
use App\Incidentes\Eventos\IncidentesCambiados;
use App\Incidentes\Models\HistorialIncidente;
use App\Incidentes\Models\Incidente;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class TraspasoDeIncidentesTest extends TestCase
{
    private User $saliente;

    private string $token;

    protected function setUp(): void
    {
        parent::setUp();

        Event::fake([IncidentesCambiados::class]);
        $this->saliente = $this->crearUsuario('Soporte N1');
        $this->token = $this->iniciarSesion($this->crearUsuario('Administrador'));
    }

    private function ruta(?User $usuario = null): string
    {
        return '/api/v1/usuarios/'.($usuario ?? $this->saliente)->id.'/incidentes-abiertos';
    }

    public function test_cuenta_solo_lo_sin_resolver_y_lo_devuelve_a_la_cola_conservando_la_historia(): void
    {
        $abiertos = collect(range(1, 3))->map(fn () => $this->incidenteAtendidoPor($this->saliente));
        $enProgreso = $this->incidenteAtendidoPor($this->saliente, ['estado' => Estado::EnProgreso]);
        $resuelto = $this->incidenteAtendidoPor($this->saliente, ['estado' => Estado::Resuelto, 'solucion' => 'Listo.']);
        $deOtra = $this->incidenteAtendidoPor($this->crearUsuario('Soporte N2'));

        $this->conToken($this->token)->getJson($this->ruta())->assertOk()->assertJsonPath('data.total', 4);
        $this->conToken($this->token)->postJson($this->ruta().'/traspaso', ['usuario_asignado_id' => null])
            ->assertOk()
            ->assertJsonPath('data.traspasados', 4);

        $this->assertSame(0, Incidente::whereIn('id', [...$abiertos->pluck('id'), $enProgreso->id])->whereNotNull('usuario_asignado_id')->count());
        $this->assertSame($this->saliente->id, $resuelto->fresh()->usuario_asignado_id, 'Lo resuelto es historia: conserva a su persona.');
        $this->assertNotNull($deOtra->fresh()->usuario_asignado_id);

        $entrada = HistorialIncidente::where('incidente_id', $enProgreso->id)->sole();
        $this->assertSame(CampoHistorial::UsuarioAsignado, $entrada->campo);
        $this->assertSame([(string) $this->saliente->id, null], [$entrada->valor_anterior, $entrada->valor_nuevo]);
        Event::assertDispatchedTimes(IncidentesCambiados::class, 1);
    }

    public function test_los_pasa_a_otra_persona_que_atiende_incidentes(): void
    {
        $incidente = $this->incidenteAtendidoPor($this->saliente);
        $destino = $this->crearUsuario('Soporte N2');

        $this->conToken($this->token)->postJson($this->ruta().'/traspaso', ['usuario_asignado_id' => $destino->id])->assertOk();

        $this->assertSame($destino->id, $incidente->fresh()->usuario_asignado_id);
    }

    public function test_el_destino_debe_ser_otra_persona_activa_que_atiende_incidentes(): void
    {
        $this->incidenteAtendidoPor($this->saliente);
        $inactiva = $this->crearUsuario('Soporte N2', ['activo' => false]);

        foreach ([$this->saliente->id, $this->crearUsuario()->id, $inactiva->id, 999_999] as $destino) {
            $this->conToken($this->token)->postJson($this->ruta().'/traspaso', ['usuario_asignado_id' => $destino])
                ->assertUnprocessable()
                ->assertJsonValidationErrors('usuario_asignado_id');
        }
        $this->conToken($this->token)->postJson($this->ruta().'/traspaso', [])->assertUnprocessable();
        $this->assertSame(1, Incidente::where('usuario_asignado_id', $this->saliente->id)->count());
    }

    public function test_solo_quien_administra_usuarios(): void
    {
        $soporte = $this->iniciarSesion($this->crearUsuario('Soporte N2'));

        $this->conToken($soporte)->getJson($this->ruta())->assertForbidden();
        $this->conToken($soporte)->postJson($this->ruta().'/traspaso', ['usuario_asignado_id' => null])->assertForbidden();
    }

    public function test_miles_se_traspasan_con_pocas_consultas(): void
    {
        $creador = $this->crearUsuario();
        $cantidad = TraspasarIncidentesAbiertos::POR_TRAMO * 2 + 50;
        Incidente::factory()->count($cantidad)->create([
            'usuario_creador_id' => $creador->id,
            'usuario_asignado_id' => $this->saliente->id,
            'estado' => Estado::Abierto,
            'solucion' => null,
        ]);

        DB::flushQueryLog();
        DB::enableQueryLog();
        $this->conToken($this->token)->postJson($this->ruta().'/traspaso', ['usuario_asignado_id' => null])
            ->assertOk()
            ->assertJsonPath('data.traspasados', $cantidad);

        $this->assertLessThan(40, count(DB::getQueryLog()), 'Se traspasa por tramos, no una consulta por incidente.');
        $this->assertSame($cantidad, HistorialIncidente::where('campo', CampoHistorial::UsuarioAsignado)->count());
    }
}
