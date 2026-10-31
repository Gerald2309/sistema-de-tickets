<?php

namespace Tests\Unit\Incidentes;

use App\Incidentes\Dominio\Jornada;
use PHPUnit\Framework\TestCase;

class JornadaTest extends TestCase
{
    public function test_el_hueco_libre_empieza_en_el_tramo_siguiente_a_la_hora_pedida(): void
    {
        $this->assertSame(630, Jornada::huecoLibre([], 620, 60), 'A las 10:20 el primer hueco es 10:30.');
        $this->assertSame(Jornada::INICIO, Jornada::huecoLibre([], 0, 60), 'Antes de la jornada, su inicio.');
    }

    public function test_salta_lo_ocupado_y_respeta_el_fin_de_la_jornada(): void
    {
        $ocupados = [[600, 690], [720, 780]];

        $this->assertSame(690, Jornada::huecoLibre($ocupados, 600, 30), 'Cabe media hora entre 11:30 y 12:00.');
        $this->assertSame(780, Jornada::huecoLibre($ocupados, 600, 60), 'Una hora no cabe en ese hueco: va después.');
        $this->assertNull(Jornada::huecoLibre($ocupados, Jornada::FIN - 30, 60), 'Ya no cabe antes del fin.');
    }

    public function test_cabe_exige_no_chocar_y_quedar_dentro_de_la_jornada(): void
    {
        $ocupados = [[600, 660]];

        $this->assertTrue(Jornada::cabe($ocupados, 660, 60), 'Pegado al final de otra no choca.');
        $this->assertFalse(Jornada::cabe($ocupados, 630, 60));
        $this->assertFalse(Jornada::cabe([], Jornada::FIN - 30, 60));
        $this->assertFalse(Jornada::cabe([], Jornada::INICIO - 30, 30));
    }
}
