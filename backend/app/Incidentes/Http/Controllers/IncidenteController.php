<?php

namespace App\Incidentes\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Incidentes\Acciones\ActualizarIncidente;
use App\Incidentes\Acciones\CrearIncidente;
use App\Incidentes\Acciones\EliminarIncidente;
use App\Incidentes\Consultas\ListarIncidentes;
use App\Incidentes\Http\Requests\ActualizarIncidenteRequest;
use App\Incidentes\Http\Requests\CrearIncidenteRequest;
use App\Incidentes\Http\Requests\ListarIncidentesRequest;
use App\Incidentes\Http\Resources\IncidenteResource;
use App\Incidentes\Models\Incidente;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class IncidenteController extends Controller
{
    public function index(ListarIncidentesRequest $request, ListarIncidentes $listarIncidentes): AnonymousResourceCollection
    {
        return IncidenteResource::collection($listarIncidentes->ejecutar($request->user(), $request->filtros()));
    }

    public function store(CrearIncidenteRequest $request, CrearIncidente $crearIncidente): JsonResponse
    {
        $incidente = $crearIncidente->ejecutar($request->user(), $request->datos());

        return (new IncidenteResource($incidente))->response()->setStatusCode(Response::HTTP_CREATED);
    }

    public function show(Incidente $incidente): IncidenteResource
    {
        return new IncidenteResource($incidente);
    }

    public function update(ActualizarIncidenteRequest $request, Incidente $incidente, ActualizarIncidente $actualizarIncidente): IncidenteResource
    {
        return new IncidenteResource($actualizarIncidente->ejecutar($request->user(), $incidente, $request->datos()));
    }

    public function destroy(Request $request, Incidente $incidente, EliminarIncidente $eliminarIncidente): Response
    {
        $eliminarIncidente->ejecutar($request->user(), $incidente);

        return response()->noContent();
    }
}
