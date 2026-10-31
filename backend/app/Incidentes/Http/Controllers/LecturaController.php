<?php

namespace App\Incidentes\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Incidentes\Acciones\MarcarConversacionLeida;
use App\Incidentes\Models\Incidente;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class LecturaController extends Controller
{
    public function __invoke(Request $request, Incidente $incidente, MarcarConversacionLeida $marcarConversacionLeida): Response
    {
        $marcarConversacionLeida->ejecutar($request->user(), $incidente);

        return response()->noContent();
    }
}
