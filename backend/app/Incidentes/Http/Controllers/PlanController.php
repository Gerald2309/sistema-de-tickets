<?php

namespace App\Incidentes\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Incidentes\Acciones\PlanearEnElDia;
use App\Incidentes\Consultas\PlanDelPeriodo;
use App\Incidentes\Http\Requests\PlanearRequest;
use App\Incidentes\Http\Requests\ReubicarEntradaRequest;
use App\Incidentes\Http\Requests\VerPlanRequest;
use App\Incidentes\Http\Resources\EntradaDelPlanResource;
use App\Incidentes\Models\EntradaDelPlan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

// La agenda de «Mi día». Cada persona solo alcanza sus entradas: las de otra responden 404.
class PlanController extends Controller
{
    public function __construct(private readonly PlanDelPeriodo $plan) {}

    public function index(VerPlanRequest $request): AnonymousResourceCollection
    {
        return EntradaDelPlanResource::collection($this->plan->ejecutar($request->user(), $request->desde(), $request->hasta()));
    }

    public function store(PlanearRequest $request, PlanearEnElDia $planear): JsonResponse
    {
        $entrada = $planear->ejecutar($request->user(), $request->que(), $request->fecha(), $request->inicio(), $request->duracion());

        return $this->respuesta($request, $entrada, $entrada->wasRecentlyCreated ? Response::HTTP_CREATED : Response::HTTP_OK);
    }

    public function update(ReubicarEntradaRequest $request, int $entrada, PlanearEnElDia $planear): JsonResponse
    {
        $propia = $this->propia($request, $entrada);
        $propia = $planear->reubicar(
            $propia,
            $request->fecha($propia),
            $request->inicio($propia),
            $request->duracion($propia),
            $request->esExacto(),
        );

        return $this->respuesta($request, $propia);
    }

    public function destroy(Request $request, int $entrada): Response
    {
        $this->propia($request, $entrada)->delete();

        return response()->noContent();
    }

    private function propia(Request $request, int $id): EntradaDelPlan
    {
        return EntradaDelPlan::de($request->user()->id)->findOrFail($id);
    }

    private function respuesta(Request $request, EntradaDelPlan $entrada, int $estado = Response::HTTP_OK): JsonResponse
    {
        $entrada->load($this->plan->relaciones($request->user()));

        return (new EntradaDelPlanResource($entrada))->response()->setStatusCode($estado);
    }
}
