<?php

namespace Tests\Feature\Incidentes;

use App\Incidentes\Dominio\CampoHistorial;
use App\Incidentes\Dominio\Estado;
use App\Incidentes\Dominio\TipoDeCambio;
use App\Incidentes\Eventos\IncidentesCambiados;
use App\Incidentes\Models\HistorialIncidente;
use App\Incidentes\Models\Incidente;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class TomarIncidenteTest extends TestCase
{
    private function ruta(Incidente $incidente): string
    {
        return "/api/v1/incidentes/{$incidente->id}/tomar";
    }

    public function test_quien_atiende_incidentes_toma_uno_libre_y_queda_asignado_sin_cambiar_el_estado(): void
    {
        $soporte = $this->crearUsuario('Soporte N1');
        $incidente = $this->incidenteDe($this->crearUsuario());
        Event::fake([IncidentesCambiados::class]);

        $this->conToken($this->iniciarSesion($soporte))->postJson($this->ruta($incidente))
            ->assertOk()
            ->assertJsonPath('data.asignado.id', $soporte->id)
            ->assertJsonPath('data.estado', 'abierto');

        $entrada = HistorialIncidente::where('incidente_id', $incidente->id)->sole();
        $this->assertSame(CampoHistorial::UsuarioAsignado, $entrada->campo);
        $this->assertSame([null, (string) $soporte->id], [$entrada->valor_anterior, $entrada->valor_nuevo]);
        Event::assertDispatched(IncidentesCambiados::class, fn (IncidentesCambiados $evento) => $evento->tipo === TipoDeCambio::Actualizado);
    }

    public function test_un_incidente_que_ya_atiende_otra_persona_responde_409_y_no_cambia(): void
    {
        $primero = $this->crearUsuario('Soporte N1');
        $incidente = $this->incidenteAtendidoPor($primero);

        $this->conToken($this->iniciarSesion($this->crearUsuario('Soporte N2')))->postJson($this->ruta($incidente))
            ->assertConflict()
            ->assertHeader('Content-Type', 'application/problem+json')
            ->assertJsonPath('type', 'urn:incidentes:problema:no-se-puede-tomar')
            ->assertJsonPath('detail', 'Este incidente ya tiene asignada a una persona.');

        $this->assertSame($primero->id, $incidente->fresh()->usuario_asignado_id);
        $this->assertSame(0, HistorialIncidente::count());
    }

    public function test_un_incidente_cerrado_no_se_toma(): void
    {
        $incidente = $this->incidenteDe($this->crearUsuario(), ['estado' => Estado::Cerrado, 'solucion' => 'Listo.']);

        $this->conToken($this->iniciarSesion($this->crearUsuario('Soporte N1')))->postJson($this->ruta($incidente))
            ->assertConflict()
            ->assertJsonPath('detail', 'Un incidente cerrado ya no se puede tomar.');

        $this->assertNull($incidente->fresh()->usuario_asignado_id);
    }

    public function test_el_solicitante_no_toma_el_suyo_ni_ve_los_ajenos(): void
    {
        $solicitante = $this->crearUsuario();
        $token = $this->iniciarSesion($solicitante);

        $this->conToken($token)->postJson($this->ruta($this->incidenteDe($solicitante)))->assertForbidden();
        $this->conToken($token)->postJson($this->ruta($this->incidenteDe($this->crearUsuario())))->assertNotFound();
    }
}
