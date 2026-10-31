<?php

namespace App\Incidentes\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Incidentes\Acciones\ActualizarIncidentesEnLote;
use App\Incidentes\Acciones\EliminarIncidentesEnLote;
use App\Incidentes\Consultas\IncidentesDelLote;
use App\Incidentes\Http\Requests\ActualizarIncidentesEnLoteRequest;
use App\Incidentes\Http\Requests\EliminarIncidentesEnLoteRequest;
use App\Incidentes\Http\Resources\IncidenteResource;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class IncidenteLoteController extends Controller
{
    public function actualizar(
        ActualizarIncidentesEnLoteRequest $request,
        IncidentesDelLote $incidentesDelLote,
        ActualizarIncidentesEnLote $actualizarIncidentesEnLote,
    ): AnonymousResourceCollection {
        $incidentes = $incidentesDelLote->ejecutar($request->user(), $request->ids());

        return IncidenteResource::collection($actualizarIncidentesEnLote->ejecutar($request->user(), $incidentes, $request->cambios()));
    }

    public function eliminar(
        EliminarIncidentesEnLoteRequest $request,
        IncidentesDelLote $incidentesDelLote,
        EliminarIncidentesEnLote $eliminarIncidentesEnLote,
    ): Response {
        $incidentes = $incidentesDelLote->ejecutar($request->user(), $request->ids());
        $eliminarIncidentesEnLote->ejecutar($request->user(), $incidentes);

        return response()->noContent();
    }
}
