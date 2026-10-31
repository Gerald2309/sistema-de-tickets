<?php

namespace Tests\Feature\Incidentes;

use App\Identidad\Models\User;
use App\Incidentes\Dominio\CampoHistorial;
use App\Incidentes\Dominio\Estado;
use App\Incidentes\Models\HistorialIncidente;
use App\Incidentes\Models\Incidente;
use App\Incidentes\Models\MensajeIncidente;
use Illuminate\Database\QueryException;
use Tests\TestCase;

// POST /incidentes/{id}/confirmacion: quien reportó el incidente dice si la solución le sirvió.
class ConfirmarSolucionTest extends TestCase
{
    private function ruta(Incidente $incidente): string
    {
        return "/api/v1/incidentes/{$incidente->id}/confirmacion";
    }

    private function resueltoDe(User $solicitante): Incidente
    {
        return $this->incidenteDe($solicitante, [
            'estado' => Estado::Resuelto,
            'solucion' => 'Se cambió el tóner.',
            'usuario_asignado_id' => $this->crearUsuario('Soporte N1')->id,
        ]);
    }

    public function test_si_quedo_solucionado_se_cierra_con_la_calificacion_y_queda_en_el_historial(): void
    {
        $solicitante = $this->crearUsuario();
        $incidente = $this->resueltoDe($solicitante);

        $this->conToken($this->iniciarSesion($solicitante))
            ->postJson($this->ruta($incidente), ['solucionado' => true, 'calificacion' => 4])
            ->assertOk()
            ->assertJsonPath('data.estado', 'cerrado')
            ->assertJsonPath('data.calificacion', 4);

        $entrada = HistorialIncidente::where('incidente_id', $incidente->id)->sole();
        $this->assertSame(CampoHistorial::Estado, $entrada->campo);
        $this->assertSame($solicitante->id, $entrada->usuario_id);
    }

    public function test_la_calificacion_es_opcional(): void
    {
        $solicitante = $this->crearUsuario();
        $incidente = $this->resueltoDe($solicitante);

        $this->conToken($this->iniciarSesion($solicitante))
            ->postJson($this->ruta($incidente), ['solucionado' => true])
            ->assertOk()
            ->assertJsonPath('data.calificacion', null);
    }

    public function test_si_no_quedo_solucionado_vuelve_a_soporte_con_el_motivo_en_la_conversacion(): void
    {
        $solicitante = $this->crearUsuario();
        $incidente = $this->resueltoDe($solicitante);

        $this->conToken($this->iniciarSesion($solicitante))
            ->postJson($this->ruta($incidente), ['solucionado' => false, 'motivo' => '  Sigue sin imprimir a color.  '])
            ->assertOk()
            ->assertJsonPath('data.estado', 'en_progreso')
            ->assertJsonPath('data.asignado.id', $incidente->usuario_asignado_id);

        $mensaje = MensajeIncidente::where('incidente_id', $incidente->id)->sole();
        $this->assertSame('Sigue sin imprimir a color.', $mensaje->cuerpo);
        $this->assertSame($solicitante->id, $mensaje->usuario_id);
        $this->assertFalse($mensaje->interno);
    }

    public function test_si_no_quedo_solucionado_el_motivo_es_obligatorio_y_no_cambia_nada(): void
    {
        $solicitante = $this->crearUsuario();
        $incidente = $this->resueltoDe($solicitante);
        $token = $this->iniciarSesion($solicitante);

        foreach ([[], ['motivo' => '   ']] as $sinMotivo) {
            $this->conToken($token)->postJson($this->ruta($incidente), ['solucionado' => false] + $sinMotivo)
                ->assertUnprocessable()
                ->assertJsonValidationErrors(['motivo' => 'Cuéntenos qué sigue pasando'], 'errors');
        }

        $this->assertSame(Estado::Resuelto, $incidente->fresh()->estado);
        $this->assertSame(0, MensajeIncidente::count());
    }

    public function test_valida_la_respuesta_la_calificacion_y_que_no_se_mezclen(): void
    {
        $solicitante = $this->crearUsuario();
        $incidente = $this->resueltoDe($solicitante);
        $token = $this->iniciarSesion($solicitante);

        $this->conToken($token)->postJson($this->ruta($incidente), [])->assertJsonValidationErrors(['solucionado'], 'errors');
        $this->conToken($token)->postJson($this->ruta($incidente), ['solucionado' => true, 'calificacion' => 6])->assertJsonValidationErrors(['calificacion'], 'errors');
        $this->conToken($token)->postJson($this->ruta($incidente), ['solucionado' => true, 'motivo' => 'x'])->assertJsonValidationErrors(['motivo'], 'errors');
        $this->conToken($token)->postJson($this->ruta($incidente), ['solucionado' => false, 'motivo' => 'x', 'calificacion' => 3])->assertJsonValidationErrors(['calificacion'], 'errors');
    }

    public function test_solo_un_incidente_resuelto_se_confirma(): void
    {
        $solicitante = $this->crearUsuario();
        $incidente = $this->incidenteDe($solicitante, ['estado' => Estado::EnProgreso, 'usuario_asignado_id' => $this->crearUsuario('Soporte N1')->id]);

        $this->conToken($this->iniciarSesion($solicitante))
            ->postJson($this->ruta($incidente), ['solucionado' => true])
            ->assertConflict()
            ->assertHeader('Content-Type', 'application/problem+json')
            ->assertJsonPath('type', 'urn:incidentes:problema:no-se-puede-confirmar');

        $this->assertSame(Estado::EnProgreso, $incidente->fresh()->estado);
    }

    public function test_la_base_rechaza_una_calificacion_fuera_de_rango_aunque_se_salte_la_validacion(): void
    {
        $incidente = $this->resueltoDe($this->crearUsuario());

        $this->expectException(QueryException::class);
        $incidente->forceFill(['calificacion' => 6])->save();
    }

    public function test_solo_lo_confirma_quien_lo_reporto(): void
    {
        $incidente = $this->resueltoDe($this->crearUsuario());

        // Otro solicitante no lo ve; soporte y el administrador lo ven, pero la decisión no es suya.
        $this->conToken($this->iniciarSesion($this->crearUsuario()))->postJson($this->ruta($incidente), ['solucionado' => true])->assertNotFound();
        $this->conToken($this->iniciarSesion($this->crearUsuario('Soporte N1')))->postJson($this->ruta($incidente), ['solucionado' => true])->assertForbidden();
        $this->conToken($this->iniciarSesion($this->crearUsuario('Administrador')))->postJson($this->ruta($incidente), ['solucionado' => true])->assertForbidden();
        $this->sinCredencial()->postJson($this->ruta($incidente), ['solucionado' => true])->assertUnauthorized();

        $this->assertSame(Estado::Resuelto, $incidente->fresh()->estado);
    }
}
