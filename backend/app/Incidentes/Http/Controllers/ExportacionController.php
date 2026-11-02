<?php

namespace App\Incidentes\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Incidentes\Consultas\IncidentesParaExportar;
use App\Incidentes\Http\Exportacion\EscritorDeIncidentes;
use App\Incidentes\Http\Requests\ExportarIncidentesRequest;
use Illuminate\Support\Facades\Date;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExportacionController extends Controller
{
    /**
     * Exportar incidentes a CSV
     */
    public function __invoke(
        ExportarIncidentesRequest $request,
        IncidentesParaExportar $incidentesParaExportar,
        EscritorDeIncidentes $escritor,
    ): StreamedResponse {
        $formato = $request->formato();
        $ahora = Date::now();
        $incidentes = $incidentesParaExportar->ejecutar($request->user(), $request->filtros());

        return response()->streamDownload(
            fn () => $escritor->escribir($formato, $incidentes, $ahora),
            'incidentes-'.$ahora->format('Ymd-His').'.'.$formato->value,
            ['Content-Type' => $formato->tipoDeContenido()],
        );
    }
}
