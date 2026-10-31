<?php

namespace App\Identidad\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Identidad\Consultas\BuscarPersonas;
use App\Identidad\Http\Requests\BuscarPersonasRequest;
use App\Identidad\Http\Resources\PersonaResource;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class PersonaController extends Controller
{
    public function __invoke(BuscarPersonasRequest $request, BuscarPersonas $buscarPersonas): AnonymousResourceCollection
    {
        return PersonaResource::collection($buscarPersonas->ejecutar(
            $request->texto(),
            $request->ids(),
            $request->incluirInactivas(),
            $request->paraAsignar(),
        ));
    }
}
