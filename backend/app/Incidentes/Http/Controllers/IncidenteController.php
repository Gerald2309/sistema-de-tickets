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
use App\Incidentes\Policies\IncidentePolicy;
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
        $incidente = $crearIncidente->ejecutar($request->user(), $request->datos(), $request->claveDeIdempotencia());
        $respuesta = (new IncidenteResource($incidente))->response();

        // Un reintento con la misma clave recibe el incidente que ya existía, marcado como repetición.
        return $incidente->wasRecentlyCreated
            ? $respuesta->setStatusCode(Response::HTTP_CREATED)
            : $respuesta->header('Idempotent-Replayed', 'true');
    }

    public function show(Request $request, Incidente $incidente): IncidenteResource
    {
        $lector = $request->user();

        return new IncidenteResource($incidente->loadCount(Incidente::conteoDeMensajesSinLeer($lector->id, IncidentePolicy::alcanceDe($lector))));
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
