<?php

namespace App\Incidentes\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Identidad\Models\User;
use App\Incidentes\Consultas\PlazosDeLaEmpresa;
use App\Incidentes\Http\Requests\PlazoPropuestoRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Date;

// Lo que tendría un incidente creado ahora por esta persona: el formulario lo propone antes de enviarlo.
class PlazoPropuestoController extends Controller
{
    public function __invoke(PlazoPropuestoRequest $request, PlazosDeLaEmpresa $plazosDeLaEmpresa): JsonResponse
    {
        /** @var User $persona */
        $persona = $request->user();
        $limites = $plazosDeLaEmpresa->limites($persona->cliente_id, $request->prioridad(), Date::now()->toImmutable());

        return response()->json(['data' => [
            'vence_respuesta_en' => $limites['respuesta']->toIso8601ZuluString(),
            'fecha_vencimiento' => $limites['resolucion']->toIso8601ZuluString(),
        ]]);
    }
}
