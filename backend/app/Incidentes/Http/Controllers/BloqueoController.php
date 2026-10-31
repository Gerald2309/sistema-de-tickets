<?php

namespace App\Incidentes\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Incidentes\Acciones\MarcarBloqueo;
use App\Incidentes\Http\Requests\BloqueoRequest;
use App\Incidentes\Models\Incidente;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class BloqueoController extends Controller
{
    public function update(BloqueoRequest $request, Incidente $incidente, MarcarBloqueo $marcar): Response
    {
        $marcar->ejecutar($request->user(), $incidente, $request->motivo());

        return response()->noContent();
    }

    public function destroy(Request $request, Incidente $incidente, MarcarBloqueo $marcar): Response
    {
        $marcar->ejecutar($request->user(), $incidente, null);

        return response()->noContent();
    }
}
