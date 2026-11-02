<?php

namespace App\Incidentes\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Incidentes\Consultas\CacheDelPanel;
use App\Incidentes\Consultas\ResumirIncidentes;
use App\Incidentes\Http\Resources\DashboardResource;
use App\Incidentes\Policies\IncidentePolicy;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Date;

class DashboardController extends Controller
{
    // Un minuto: lo único que cambia sin que cambie un incidente son los vencidos, con el reloj.
    private const SEGUNDOS = 60;

    /**
     * Resumen del panel
     */
    public function __invoke(Request $request, ResumirIncidentes $resumirIncidentes, CacheDelPanel $cache): DashboardResource
    {
        $alcance = IncidentePolicy::alcanceDe($request->user());

        return new DashboardResource($cache->recordar('resumen', $alcance, [], self::SEGUNDOS,
            fn () => $resumirIncidentes->ejecutar($alcance, Date::now())));
    }
}
