<?php

namespace App\Incidentes\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Incidentes\Consultas\UltimoTrabajado;
use App\Incidentes\Http\Resources\IncidenteDelPlanResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UltimoTrabajadoController extends Controller
{
    public function __invoke(Request $request, UltimoTrabajado $ultimoTrabajado): JsonResponse
    {
        $ultimo = $ultimoTrabajado->ejecutar($request->user());

        return response()->json(['data' => $ultimo === null ? null : [
            'incidente' => new IncidenteDelPlanResource($ultimo['incidente']),
            'accion' => $ultimo['accion'],
            'momento' => $ultimo['momento']->copy()->utc()->toIso8601ZuluString(),
        ]]);
    }
}
