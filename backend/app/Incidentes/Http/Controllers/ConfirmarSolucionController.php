<?php

namespace App\Incidentes\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Incidentes\Acciones\ConfirmarSolucion;
use App\Incidentes\Http\Requests\ConfirmarSolucionRequest;
use App\Incidentes\Http\Resources\IncidenteResource;
use App\Incidentes\Models\Incidente;

class ConfirmarSolucionController extends Controller
{
    /**
     * Confirmar o rechazar la solución
     */
    public function __invoke(ConfirmarSolucionRequest $request, Incidente $incidente, ConfirmarSolucion $confirmarSolucion): IncidenteResource
    {
        return new IncidenteResource($confirmarSolucion->ejecutar(
            $request->user(),
            $incidente,
            $request->solucionado(),
            $request->motivo(),
            $request->calificacion(),
        ));
    }
}
