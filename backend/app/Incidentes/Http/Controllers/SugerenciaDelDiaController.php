<?php

namespace App\Incidentes\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Incidentes\Acciones\SugerirDia;
use App\Incidentes\Consultas\PlanDelPeriodo;
use App\Incidentes\Http\Requests\DiaRequest;
use App\Incidentes\Http\Resources\EntradaDelPlanResource;
use App\Incidentes\Models\EntradaDelPlan;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class SugerenciaDelDiaController extends Controller
{
    public function __invoke(DiaRequest $request, SugerirDia $sugerir, PlanDelPeriodo $plan): AnonymousResourceCollection
    {
        /** @var Collection<int, EntradaDelPlan> $agregadas */
        $agregadas = new Collection($sugerir->ejecutar($request->user(), $request->fecha(), $request->desde()));

        return EntradaDelPlanResource::collection($agregadas->load($plan->relaciones($request->user())));
    }
}
