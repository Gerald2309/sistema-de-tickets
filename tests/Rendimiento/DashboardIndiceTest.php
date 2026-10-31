<?php

namespace Tests\Rendimiento;

use App\Incidentes\Consultas\ResumirIncidentes;
use App\Incidentes\Policies\IncidentePolicy;
use Illuminate\Support\Facades\DB;

// Decisión sobre el índice (estado, prioridad, deleted_at): se conserva si la consulta combinada con ese
// índice mejora frente a las cuatro consultas del modelo sin él. Mismo método para todas: tiempo de las
// sentencias a través de la aplicación, p95 de 50 ejecuciones.
class DashboardIndiceTest extends PruebaDeRendimiento
{
    private const INDICE = 'incidentes_estado_prioridad_deleted_at_index';

    public function test_comparacion_de_las_consultas_del_dashboard(): void
    {
        $this->assertNotEmpty(DB::select('SHOW INDEX FROM incidentes WHERE Key_name = ?', [self::INDICE]), 'El índice del dashboard no existe.');

        $sinIndice = 'IGNORE INDEX ('.self::INDICE.')';
        $vencidos = "SELECT COUNT(*) FROM incidentes {$sinIndice} WHERE deleted_at IS NULL AND estado IN ('abierto', 'en_progreso') AND fecha_vencimiento < NOW()";

        $alcance = IncidentePolicy::alcanceDe($this->administrador);

        $this->medir('A: dos consultas combinadas, con el índice (la actual)', fn () => app(ResumirIncidentes::class)->ejecutar($alcance, now()));
        $this->medir('B: las mismas dos consultas, sin el índice', function () use ($sinIndice, $vencidos) {
            DB::select("SELECT estado, prioridad, COUNT(*) FROM incidentes {$sinIndice} WHERE deleted_at IS NULL GROUP BY estado, prioridad");
            DB::select($vencidos);
        });
        $this->medir('C: cuatro consultas del modelo, sin el índice', function () use ($sinIndice, $vencidos) {
            DB::select("SELECT COUNT(*) FROM incidentes {$sinIndice} WHERE deleted_at IS NULL");
            DB::select("SELECT estado, COUNT(*) FROM incidentes {$sinIndice} WHERE deleted_at IS NULL GROUP BY estado");
            DB::select("SELECT prioridad, COUNT(*) FROM incidentes {$sinIndice} WHERE deleted_at IS NULL GROUP BY prioridad");
            DB::select($vencidos);
        });
        $this->medir('D: cuatro consultas del modelo, con todos los índices disponibles', function () {
            DB::select('SELECT COUNT(*) FROM incidentes WHERE deleted_at IS NULL');
            DB::select('SELECT estado, COUNT(*) FROM incidentes WHERE deleted_at IS NULL GROUP BY estado');
            DB::select('SELECT prioridad, COUNT(*) FROM incidentes WHERE deleted_at IS NULL GROUP BY prioridad');
            DB::select("SELECT COUNT(*) FROM incidentes WHERE deleted_at IS NULL AND estado IN ('abierto', 'en_progreso') AND fecha_vencimiento < NOW()");
        });
    }
}
