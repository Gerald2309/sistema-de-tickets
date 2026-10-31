<?php

namespace Tests\Unit\Incidentes;

use App\Incidentes\Dominio\ResumenDeIncidentes;
use PHPUnit\Framework\TestCase;

class ResumenDeIncidentesTest extends TestCase
{
    public function test_sin_incidentes_todo_sale_en_cero_con_todos_los_estados_y_prioridades(): void
    {
        $resumen = ResumenDeIncidentes::desde([], 0);

        $this->assertSame(0, $resumen->total);
        $this->assertSame(['abierto' => 0, 'en_progreso' => 0, 'resuelto' => 0, 'cerrado' => 0], $resumen->porEstado);
        $this->assertSame(['baja' => 0, 'media' => 0, 'alta' => 0, 'critica' => 0], $resumen->porPrioridad);
    }

    public function test_el_total_y_los_dos_agrupados_salen_de_las_mismas_filas(): void
    {
        $resumen = ResumenDeIncidentes::desde([
            ['estado' => 'abierto', 'prioridad' => 'alta', 'cantidad' => 3],
            ['estado' => 'abierto', 'prioridad' => 'baja', 'cantidad' => 2],
            ['estado' => 'cerrado', 'prioridad' => 'alta', 'cantidad' => 4],
        ], 1);

        $this->assertSame(9, $resumen->total);
        $this->assertSame(5, $resumen->porEstado['abierto']);
        $this->assertSame(7, $resumen->porPrioridad['alta']);
        $this->assertSame($resumen->total, array_sum($resumen->porEstado));
        $this->assertSame($resumen->total, array_sum($resumen->porPrioridad));
        $this->assertSame(1, $resumen->vencidos);
    }
}
