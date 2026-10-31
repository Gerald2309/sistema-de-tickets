<?php

namespace App\Incidentes\Consultas;

use App\Incidentes\Dominio\AlcanceIncidentes;
use App\Incidentes\Dominio\ResumenDeIncidentes;
use App\Incidentes\Models\Incidente;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class ResumirIncidentes
{
    /**
     * Dos consultas sobre lo que la persona puede ver, sin hidratar modelos. Van en una transacción
     * para leer la misma instantánea: los vencidos nunca superan el total.
     */
    public function ejecutar(AlcanceIncidentes $alcance, Carbon $ahora): ResumenDeIncidentes
    {
        return DB::transaction(function () use ($alcance, $ahora) {
            $grupos = Incidente::query()->visiblePara($alcance)->toBase()
                ->selectRaw('estado, prioridad, count(*) as cantidad')
                ->groupBy('estado', 'prioridad')
                ->get()
                ->map(fn (object $fila) => ['estado' => $fila->estado, 'prioridad' => $fila->prioridad, 'cantidad' => (int) $fila->cantidad]);

            $vencidosPorEstado = Incidente::query()->visiblePara($alcance)->vencidosAl($ahora)->toBase()
                ->selectRaw('estado, count(*) as cantidad')
                ->groupBy('estado')
                ->pluck('cantidad', 'estado')
                ->map(fn (mixed $cantidad) => (int) $cantidad)
                ->all();

            return ResumenDeIncidentes::desde($grupos, $vencidosPorEstado);
        });
    }
}
