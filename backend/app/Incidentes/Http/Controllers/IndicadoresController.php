<?php

namespace App\Incidentes\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Incidentes\Consultas\MedirIndicadores;
use App\Incidentes\Http\Requests\IndicadoresRequest;
use App\Incidentes\Http\Resources\IndicadoresResource;
use App\Incidentes\Policies\IncidentePolicy;
use Illuminate\Support\Facades\Date;

class IndicadoresController extends Controller
{
    public function __invoke(IndicadoresRequest $request, MedirIndicadores $medirIndicadores): IndicadoresResource
    {
        $alcance = IncidentePolicy::alcanceDe($request->user());

        return new IndicadoresResource($medirIndicadores->ejecutar($alcance, Date::now()->toImmutable(), $request->desfase()));
    }
}
