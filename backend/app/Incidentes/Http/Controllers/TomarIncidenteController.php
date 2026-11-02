<?php

namespace App\Incidentes\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Incidentes\Acciones\TomarIncidente;
use App\Incidentes\Http\Resources\IncidenteResource;
use App\Incidentes\Models\Incidente;
use Illuminate\Http\Request;

class TomarIncidenteController extends Controller
{
    /**
     * Tomar un incidente
     */
    public function __invoke(Request $request, Incidente $incidente, TomarIncidente $tomarIncidente): IncidenteResource
    {
        return new IncidenteResource($tomarIncidente->ejecutar($request->user(), $incidente));
    }
}
