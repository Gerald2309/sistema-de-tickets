<?php

namespace Tests\Unit\Incidentes;

use App\Incidentes\Dominio\HorarioLaboral;
use App\Incidentes\Dominio\PlazosDeServicio;
use App\Incidentes\Dominio\Prioridad;
use Carbon\CarbonImmutable;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class HorarioLaboralTest extends TestCase
{
    /**
     * El 2 de noviembre de 2026 es lunes.
     *
     * @return array<string, array{string, int, string}>
     */
    public static function casos(): array
    {
        return [
            'dentro del día' => ['2026-11-02 09:00', 60, '2026-11-02 10:00'],
            'justo al cierre' => ['2026-11-02 17:00', 60, '2026-11-02 18:00'],
            'lo que no cabe sigue al día siguiente' => ['2026-11-02 17:00', 90, '2026-11-03 08:30'],
            'antes de abrir cuenta desde la apertura' => ['2026-11-02 06:00', 30, '2026-11-02 08:30'],
            'después de cerrar cuenta desde la apertura siguiente' => ['2026-11-02 20:00', 30, '2026-11-03 08:30'],
            'el viernes en la noche empieza el lunes' => ['2026-11-06 22:00', 60, '2026-11-09 09:00'],
            'el sábado empieza el lunes' => ['2026-11-07 10:00', 60, '2026-11-09 09:00'],
            'el viernes en la tarde salta el fin de semana' => ['2026-11-06 17:00', 120, '2026-11-09 09:00'],
            'tres jornadas completas' => ['2026-11-02 08:00', 1800, '2026-11-04 18:00'],
        ];
    }

    #[DataProvider('casos')]
    public function test_suma_solo_minutos_de_trabajo(string $desde, int $minutos, string $esperado): void
    {
        $this->assertSame($esperado, HorarioLaboral::sumar(CarbonImmutable::parse($desde), $minutos)->format('Y-m-d H:i'));
    }

    public function test_los_plazos_corren_de_forma_continua_o_en_horario_laboral_segun_el_acuerdo(): void
    {
        // Viernes a las 17:00: la crítica predeterminada vence en 15 minutos y en 2 horas corridas.
        $inicio = CarbonImmutable::parse('2026-11-06 17:00');

        $continuos = PlazosDeServicio::predeterminados()->limitesDesde($inicio, Prioridad::Critica);
        $laborales = (new PlazosDeServicio(true, PlazosDeServicio::PREDETERMINADOS))->limitesDesde($inicio, Prioridad::Critica);

        $this->assertSame('2026-11-06 17:15', $continuos['respuesta']->format('Y-m-d H:i'));
        $this->assertSame('2026-11-06 19:00', $continuos['resolucion']->format('Y-m-d H:i'));
        $this->assertSame('2026-11-06 17:15', $laborales['respuesta']->format('Y-m-d H:i'));
        $this->assertSame('2026-11-09 09:00', $laborales['resolucion']->format('Y-m-d H:i'));
    }
}
