<?php

namespace Tests\Feature\Incidentes;

use App\Identidad\Dominio\ClavePermiso;
use App\Identidad\Models\User;
use App\Incidentes\Dominio\CampoHistorial;
use App\Incidentes\Dominio\Estado;
use App\Incidentes\Dominio\EventoHistorial;
use App\Incidentes\Dominio\Prioridad;
use App\Incidentes\Models\HistorialIncidente;
use App\Incidentes\Models\Incidente;
use Illuminate\Support\Carbon;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class IndicadoresDelPanelTest extends TestCase
{
    private User $creador;

    protected function setUp(): void
    {
        parent::setUp();

        $this->prepararDatosBase();
        $this->travelTo(Carbon::parse('2026-10-31 15:00:00', 'UTC'));
        $this->creador = $this->crearUsuario();
    }

    private function indicadores(string $consulta = '', ?User $quien = null): TestResponse
    {
        $quien ??= $this->usuarioConPermisos(ClavePermiso::VerDashboard, ClavePermiso::VerTodos);

        return $this->conToken($this->iniciarSesion($quien))->getJson('/api/v1/dashboard/indicadores'.$consulta);
    }

    /**
     * Resuelto hace $haceHoras, tras $tardoHoras desde que se creó.
     *
     * @param  array<string, mixed>  $atributos
     */
    private function resuelto(float $tardoHoras, float $haceHoras = 24, array $atributos = []): Incidente
    {
        $resuelto = now()->subMinutes((int) ($haceHoras * 60));

        return $this->incidenteDe($this->creador, $atributos + [
            'estado' => Estado::Resuelto,
            'solucion' => 'Listo.',
            'created_at' => $resuelto->copy()->subMinutes((int) ($tardoHoras * 60)),
            'resuelto_en' => $resuelto,
            'fecha_vencimiento' => $resuelto->copy()->addHour(),
        ]);
    }

    private function cambioDeEstado(Incidente $incidente, Estado $de, Estado $a, Carbon $momento): void
    {
        HistorialIncidente::insert([
            'incidente_id' => $incidente->id,
            'usuario_id' => $this->creador->id,
            'evento' => EventoHistorial::Modificacion->value,
            'campo' => CampoHistorial::Estado->value,
            'valor_anterior' => $de->value,
            'valor_nuevo' => $a->value,
            'created_at' => $momento,
        ]);
    }

    public function test_reparte_lo_pendiente_segun_cuanto_lleva_esperando(): void
    {
        foreach ([2, 30, 100, 300, 1000] as $horas) {
            $this->incidenteDe($this->creador, ['created_at' => now()->subHours($horas)]);
        }
        $this->incidenteDe($this->creador, ['estado' => Estado::EnProgreso, 'created_at' => now()->subHours(5)]);
        // Lo resuelto ya no espera.
        $this->resuelto(tardoHoras: 2000);

        $this->indicadores()->assertOk()->assertJsonPath('data.espera', [
            ['hasta_dias' => 1, 'cantidad' => 2],
            ['hasta_dias' => 3, 'cantidad' => 1],
            ['hasta_dias' => 7, 'cantidad' => 1],
            ['hasta_dias' => 28, 'cantidad' => 1],
            ['hasta_dias' => null, 'cantidad' => 1],
        ]);
    }

    public function test_la_tendencia_cuenta_por_dia_local_de_quien_consulta(): void
    {
        $this->travelTo(Carbon::parse('2026-10-31 03:00:00', 'UTC'));
        // 02:00 UTC es todavía el 30 en Colombia (UTC−5).
        $this->incidenteDe($this->creador, ['created_at' => now()->subHour()]);
        $this->resuelto(tardoHoras: 30, haceHoras: 26);

        $tendencia = $this->indicadores('?desfase=-300')->assertOk()->json('data.tendencia');

        $this->assertCount(14, $tendencia);
        $this->assertSame('2026-10-17', $tendencia[0]['fecha']);
        $this->assertSame(['fecha' => '2026-10-30', 'entraron' => 1, 'resueltos' => 0], $tendencia[13]);
        $this->assertSame(['fecha' => '2026-10-29', 'entraron' => 0, 'resueltos' => 1], $tendencia[12]);
        $this->assertSame(['fecha' => '2026-10-28', 'entraron' => 1, 'resueltos' => 0], $tendencia[11]);
    }

    public function test_mide_la_mediana_de_resolucion_lo_resuelto_a_tiempo_y_el_mes_anterior(): void
    {
        $this->resuelto(tardoHoras: 1, atributos: ['prioridad' => Prioridad::Critica]);
        $this->resuelto(tardoHoras: 3, atributos: ['prioridad' => Prioridad::Critica]);
        // Venció antes de resolverse.
        $this->resuelto(tardoHoras: 50, atributos: ['fecha_vencimiento' => now()->subDays(3)]);
        $this->resuelto(tardoHoras: 10, haceHoras: 24 * 40);
        // Fuera de los dos periodos.
        $this->resuelto(tardoHoras: 99, haceHoras: 24 * 70);

        $this->indicadores()->assertOk()
            ->assertJsonPath('data.resolucion.dias', 30)
            ->assertJsonPath('data.resolucion.mediana_segundos', 3 * 3600)
            ->assertJsonPath('data.resolucion.mediana_anterior_segundos', 10 * 3600)
            ->assertJsonPath('data.resolucion.a_tiempo', 0.667)
            ->assertJsonPath('data.resolucion.por_prioridad.critica', ['mediana_segundos' => 2 * 3600, 'a_tiempo' => 1, 'cantidad' => 2])
            ->assertJsonPath('data.resolucion.por_prioridad.baja', ['mediana_segundos' => null, 'a_tiempo' => null, 'cantidad' => 0]);
    }

    public function test_mide_cuanto_tarda_en_empezar_a_atenderse_y_que_parte_de_las_soluciones_se_devuelve(): void
    {
        $rapido = $this->incidenteDe($this->creador, ['estado' => Estado::EnProgreso, 'created_at' => now()->subHours(10)]);
        $this->cambioDeEstado($rapido, Estado::Abierto, Estado::EnProgreso, now()->subHours(9));
        $lento = $this->incidenteDe($this->creador, ['estado' => Estado::EnProgreso, 'created_at' => now()->subHours(10)]);
        $this->cambioDeEstado($lento, Estado::Abierto, Estado::EnProgreso, now()->subHours(7));

        $devuelto = $this->resuelto(tardoHoras: 5);
        $this->cambioDeEstado($devuelto, Estado::Resuelto, Estado::EnProgreso, now()->subHours(20));
        foreach (range(1, 3) as $n) {
            $this->cambioDeEstado($this->resuelto(tardoHoras: 5), Estado::Resuelto, Estado::Cerrado, now()->subHours(20));
        }
        // Una respuesta de hace más de un mes no cuenta.
        $this->cambioDeEstado($devuelto, Estado::Resuelto, Estado::EnProgreso, now()->subDays(40));

        $this->indicadores()->assertOk()
            ->assertJsonPath('data.resolucion.hasta_atender_segundos', 2 * 3600)
            ->assertJsonPath('data.resolucion.devueltos', 0.25);
    }

    public function test_sin_datos_los_tiempos_son_nulos_y_no_cero(): void
    {
        $this->indicadores()->assertOk()
            ->assertJsonPath('data.resolucion.mediana_segundos', null)
            ->assertJsonPath('data.resolucion.a_tiempo', null)
            ->assertJsonPath('data.resolucion.hasta_atender_segundos', null)
            ->assertJsonPath('data.resolucion.devueltos', null);
    }

    public function test_quien_ve_solo_lo_propio_mide_solo_lo_suyo(): void
    {
        $solicitante = $this->usuarioConPermisos(ClavePermiso::VerDashboard, ClavePermiso::VerPropios);
        $this->incidenteDe($solicitante, ['created_at' => now()->subHour()]);
        $this->incidenteDe($this->creador, ['created_at' => now()->subHour()]);
        $this->resuelto(tardoHoras: 4);

        $respuesta = $this->indicadores(quien: $solicitante)->assertOk();

        $this->assertSame(1, $respuesta->json('data.espera.0.cantidad'));
        $this->assertNull($respuesta->json('data.resolucion.mediana_segundos'));
    }

    public function test_rechaza_un_desfase_que_no_es_una_zona_horaria(): void
    {
        $this->indicadores('?desfase=900')->assertUnprocessable()->assertJsonValidationErrors('desfase');
    }

    public function test_exige_permiso_para_ver_el_panel(): void
    {
        $this->indicadores(quien: $this->usuarioConPermisos(ClavePermiso::VerTodos))->assertForbidden();
    }
}
