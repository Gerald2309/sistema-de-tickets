<?php

namespace App\Incidentes\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Incidentes\Consultas\IncidentesSimilares;
use App\Incidentes\Http\Resources\SimilarResource;
use App\Incidentes\Models\Incidente;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class SimilaresController extends Controller
{
    /**
     * Incidentes similares
     */
    public function __invoke(Request $request, Incidente $incidente, IncidentesSimilares $incidentesSimilares): AnonymousResourceCollection
    {
        return SimilarResource::collection($incidentesSimilares->ejecutar($request->user(), $incidente));
    }
}
