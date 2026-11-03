<?php

namespace Tests\Feature\Automatizaciones;

use App\Automatizaciones\Dominio\Disparador;
use App\Automatizaciones\Dominio\PlazoDelSla;
use App\Automatizaciones\Models\Automatizacion;
use App\Automatizaciones\Models\EjecucionDeAutomatizacion;
use App\Identidad\Models\User;
use App\Incidentes\Dominio\Estado;
use App\Incidentes\Models\Incidente;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Date;
use Tests\TestCase;

class RevisarPorTiempoTest extends TestCase
{
    private const NOTA = ['tipo' => 'accion', 'accion' => 'nota_interna', 'texto' => 'Revisar'];

    private CarbonImmutable $ahora;

    private User $solicitante;

    protected function setUp(): void
    {
        parent::setUp();

        $this->prepararDatosBase();
        $this->ahora = CarbonImmutable::parse('2026-03-10 12:00:00');
        Date::setTestNow($this->ahora);
        $this->solicitante = $this->crearUsuario();
    }

    /**
     * @param  array<string, mixed>  $atributos
     * @param  list<array<string, mixed>>  $pasos
     */
    private function automatizacion(Disparador $disparador, array $atributos = [], array $pasos = [self::NOTA]): Automatizacion
    {
        return Automatizacion::factory()->conPasos($pasos)->create(['disparador' => $disparador] + $atributos + [
            'autor_id' => $this->crearUsuario('Administrador')->id,
        ]);
    }

    /**
     * Creado hace esos minutos, sin respuesta todavía.
     *
     * @param  array<string, mixed>  $atributos
     */
    private function incidente(int $minutosAtras, array $atributos = []): Incidente
    {
        return $this->incidenteDe($this->solicitante, $atributos + ['created_at' => $this->ahora->subMinutes($minutosAtras)]);
    }

    /**
     * @return list<int>
     */
    private function revisar(): array
    {
        $this->artisan('automatizaciones:revisar')->assertSuccessful();

        return EjecucionDeAutomatizacion::orderBy('incidente_id')->pluck('incidente_id')->all();
    }

    public function test_sin_respuesta_por_un_tiempo(): void
    {
        $this->automatizacion(Disparador::SinRespuesta, ['espera_minutos' => 240]);
        $esperando = $this->incidente(300);
        $this->incidente(180);
        $this->incidente(300, ['primera_respuesta_en' => $this->ahora->subMinutes(200)]);
        $this->incidente(300, ['estado' => Estado::Resuelto, 'solucion' => 'Listo.']);

        $this->assertSame([$esperando->id], $this->revisar());
        $this->assertSame(['Revisar'], $esperando->mensajes()->pluck('cuerpo')->all());
    }

    public function test_resuelto_sin_confirmar_por_unos_dias_se_cierra(): void
    {
        $this->automatizacion(Disparador::ResueltoSinConfirmar, ['espera_minutos' => 3 * 24 * 60], [
            ['tipo' => 'accion', 'accion' => 'cerrar', 'mensaje' => 'Lo cerramos porque no recibimos confirmación.'],
        ]);
        $resuelto = ['estado' => Estado::Resuelto, 'solucion' => 'Se cambió el cable.'];
        $olvidado = $this->incidente(10000, $resuelto + ['resuelto_en' => $this->ahora->subDays(4)]);
        $reciente = $this->incidente(10000, $resuelto + ['resuelto_en' => $this->ahora->subDay()]);

        $this->assertSame([$olvidado->id], $this->revisar());
        $this->assertSame(Estado::Cerrado, $olvidado->refresh()->estado);
        $this->assertSame(['Lo cerramos porque no recibimos confirmación.'], $olvidado->mensajes()->pluck('cuerpo')->all());
        $this->assertSame(Estado::Resuelto, $reciente->refresh()->estado);
    }

    public function test_sla_por_vencer_cuando_paso_ese_porcentaje_del_plazo(): void
    {
        $this->automatizacion(Disparador::SlaPorVencer, ['porcentaje' => 80, 'plazo' => PlazoDelSla::Respuesta]);
        // Todos con 100 minutos para responder.
        $alNoventa = $this->incidente(90, ['vence_respuesta_en' => $this->ahora->addMinutes(10)]);
        $this->incidente(70, ['vence_respuesta_en' => $this->ahora->addMinutes(30)]);
        $this->incidente(110, ['vence_respuesta_en' => $this->ahora->subMinutes(10)]);
        $this->incidente(90, ['vence_respuesta_en' => $this->ahora->addMinutes(10), 'primera_respuesta_en' => $this->ahora]);
        $this->incidente(90);

        $this->assertSame([$alNoventa->id], $this->revisar());
    }

    public function test_sla_de_resolucion_vencido(): void
    {
        $this->automatizacion(Disparador::SlaVencido, ['plazo' => PlazoDelSla::Resolucion]);
        $vencido = $this->incidente(5000, ['estado' => Estado::EnProgreso, 'fecha_vencimiento' => $this->ahora->subHour()]);
        $this->incidente(5000, ['estado' => Estado::Resuelto, 'solucion' => 'Listo.', 'fecha_vencimiento' => $this->ahora->subHour()]);
        $this->incidente(5000, ['fecha_vencimiento' => $this->ahora->addHour()]);

        $this->assertSame([$vencido->id], $this->revisar());
    }

    public function test_cada_incidente_la_recibe_una_sola_vez(): void
    {
        $this->automatizacion(Disparador::SinRespuesta, ['espera_minutos' => 60]);
        $esperando = $this->incidente(120);

        $this->revisar();
        Date::setTestNow($this->ahora->addHour());

        $this->assertSame([$esperando->id], $this->revisar());
        $this->assertSame(1, $esperando->mensajes()->count());
    }

    public function test_no_revisa_las_apagadas_ni_las_de_creacion(): void
    {
        $this->automatizacion(Disparador::SinRespuesta, ['espera_minutos' => 60, 'activa' => false]);
        $this->automatizacion(Disparador::Creacion);
        $this->incidente(120);

        $this->assertSame([], $this->revisar());
    }
}
