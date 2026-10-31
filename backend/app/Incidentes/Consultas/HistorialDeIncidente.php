<?php

namespace App\Incidentes\Consultas;

use App\Incidentes\Models\HistorialIncidente;
use App\Incidentes\Models\Incidente;
use Illuminate\Contracts\Pagination\CursorPaginator;

class HistorialDeIncidente
{
    public const POR_PAGINA = 10;

    /**
     * Cambios del incidente, del más reciente al más antiguo (el id crece con el tiempo), por tramos: un
     * incidente muy trabajado no se entrega entero. Paginación por cursor: «ver más» pide los anteriores al
     * último mostrado, sin contar ni saltar filas.
     *
     * @return CursorPaginator<int, HistorialIncidente>
     */
    public function ejecutar(Incidente $incidente): CursorPaginator
    {
        return HistorialIncidente::where('incidente_id', $incidente->id)
            ->with('autor:id,name')
            ->orderByDesc('id')
            ->cursorPaginate(self::POR_PAGINA);
    }
}
