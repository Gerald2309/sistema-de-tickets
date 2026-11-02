<?php

namespace App\Incidentes\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Incidentes\Consultas\CacheDelPanel;
use App\Incidentes\Consultas\MedirIndicadores;
use App\Incidentes\Http\Requests\IndicadoresRequest;
use App\Incidentes\Http\Resources\IndicadoresResource;
use App\Incidentes\Policies\IncidentePolicy;
use Illuminate\Support\Facades\Date;

class IndicadoresController extends Controller
{
    // Cinco minutos: miran semanas hacia atrás, y un cambio en un incidente los renueva antes.
    private const SEGUNDOS = 300;

    public function __invoke(IndicadoresRequest $request, MedirIndicadores $medirIndicadores, CacheDelPanel $cache): IndicadoresResource
    {
        $alcance = IncidentePolicy::alcanceDe($request->user());
        $desfase = $request->desfase();

        return new IndicadoresResource($cache->recordar('indicadores', $alcance, [$desfase], self::SEGUNDOS,
            fn () => $medirIndicadores->ejecutar($alcance, Date::now()->toImmutable(), $desfase)));
    }
}
