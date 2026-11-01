<?php

namespace Tests\Unit\Incidentes;

use App\Incidentes\Dominio\Estado;
use Carbon\CarbonImmutable;
use Database\Seeders\VidaDeUnIncidente;
use PHPUnit\Framework\TestCase;

class VidaDeUnIncidenteTest extends TestCase
{
    public function test_los_pasos_siguen_el_ciclo_de_vida_en_orden_y_nunca_llegan_al_futuro(): void
    {
        $ahora = CarbonImmutable::parse('2026-11-02 15:00:00');

        foreach (range(1, 300) as $n) {
            $creado = $ahora->subHours($n);
            $vida = VidaDeUnIncidente::simular($creado, ['respuesta' => $creado->addHour(), 'resolucion' => $creado->addHours(8)], $ahora);

            $anterior = $creado;
            $estado = Estado::Abierto;
            foreach ($vida->pasos as $paso) {
                $this->assertSame($estado, $paso['de']);
                $this->assertTrue($estado->puedePasarA($paso['a']), 'Toda transición es una permitida.');
                $this->assertTrue($paso['momento'] >= $anterior);
                $this->assertTrue($paso['momento'] < $ahora);
                $anterior = $paso['momento'];
                $estado = $paso['a'];
            }

            $this->assertSame($estado, $vida->estado());
            $this->assertSame(in_array($estado, [Estado::Resuelto, Estado::Cerrado], true), $vida->resuelto() !== null);
        }
    }

    public function test_lo_que_se_acaba_de_crear_sigue_abierto(): void
    {
        $ahora = CarbonImmutable::parse('2026-11-02 15:00:00');

        $vida = VidaDeUnIncidente::simular($ahora->subMinute(), ['respuesta' => $ahora->addDay(), 'resolucion' => $ahora->addDays(2)], $ahora);

        $this->assertSame(Estado::Abierto, $vida->estado());
        $this->assertNull($vida->primeraRespuesta());
        $this->assertEquals($ahora->subMinute(), $vida->actualizado());
    }
}
