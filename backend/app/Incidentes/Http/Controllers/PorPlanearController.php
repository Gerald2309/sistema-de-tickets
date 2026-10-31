<?php

namespace App\Incidentes\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Incidentes\Consultas\PorPlanear;
use App\Incidentes\Http\Requests\DiaRequest;
use App\Incidentes\Http\Requests\PorPlanearRequest;
use App\Incidentes\Http\Resources\IncidenteDelPlanResource;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class PorPlanearController extends Controller
{
    public function __construct(private readonly PorPlanear $porPlanear) {}

    public function index(PorPlanearRequest $request): AnonymousResourceCollection
    {
        return IncidenteDelPlanResource::collection($this->porPlanear->ejecutar($request->user(), $request->buscar()));
    }

    public function pendientes(DiaRequest $request): AnonymousResourceCollection
    {
        return IncidenteDelPlanResource::collection($this->porPlanear->pendientes($request->user(), $request->fecha()));
    }
}
