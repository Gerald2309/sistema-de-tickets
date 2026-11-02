<?php

namespace App\Incidentes\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Incidentes\Consultas\HistorialDeIncidente;
use App\Incidentes\Http\Resources\HistorialResource;
use App\Incidentes\Models\Incidente;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class HistorialController extends Controller
{
    /**
     * Historial de cambios de un incidente
     */
    public function __invoke(Incidente $incidente, HistorialDeIncidente $historialDeIncidente): AnonymousResourceCollection
    {
        return HistorialResource::collection($historialDeIncidente->ejecutar($incidente));
    }
}
