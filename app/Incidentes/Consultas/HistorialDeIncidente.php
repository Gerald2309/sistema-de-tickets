<?php

namespace App\Incidentes\Consultas;

use App\Incidentes\Models\HistorialIncidente;
use App\Incidentes\Models\Incidente;
use Illuminate\Database\Eloquent\Collection;

class HistorialDeIncidente
{
    /**
     * Cambios del incidente en orden cronológico (el id crece con el tiempo). Sin paginar: el
     * volumen por incidente es pequeño.
     *
     * @return Collection<int, HistorialIncidente>
     */
    public function ejecutar(Incidente $incidente): Collection
    {
        return HistorialIncidente::where('incidente_id', $incidente->id)
            ->with('autor:id,name')
            ->orderBy('id')
            ->get();
    }
}
