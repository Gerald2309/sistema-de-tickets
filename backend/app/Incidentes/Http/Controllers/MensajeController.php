<?php

namespace App\Incidentes\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Incidentes\Acciones\EscribirMensaje;
use App\Incidentes\Consultas\ConversacionDeIncidente;
use App\Incidentes\Http\Requests\EscribirMensajeRequest;
use App\Incidentes\Http\Resources\MensajeResource;
use App\Incidentes\Models\Incidente;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class MensajeController extends Controller
{
    public function index(Request $request, Incidente $incidente, ConversacionDeIncidente $conversacion): AnonymousResourceCollection
    {
        return MensajeResource::collection($conversacion->ejecutar($request->user(), $incidente));
    }

    public function store(EscribirMensajeRequest $request, Incidente $incidente, EscribirMensaje $escribirMensaje): JsonResponse
    {
        $mensaje = $escribirMensaje->ejecutar($request->user(), $incidente, $request->cuerpo(), $request->interno(), $request->imagenes());

        return (new MensajeResource($mensaje))->response()->setStatusCode(Response::HTTP_CREATED);
    }
}
