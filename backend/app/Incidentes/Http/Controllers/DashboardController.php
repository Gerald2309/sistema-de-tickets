<?php

namespace App\Incidentes\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Incidentes\Consultas\ResumirIncidentes;
use App\Incidentes\Http\Resources\DashboardResource;
use App\Incidentes\Policies\IncidentePolicy;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Date;

class DashboardController extends Controller
{
    public function __invoke(Request $request, ResumirIncidentes $resumirIncidentes): DashboardResource
    {
        $alcance = IncidentePolicy::alcanceDe($request->user());

        return new DashboardResource($resumirIncidentes->ejecutar($alcance, Date::now()));
    }
}
