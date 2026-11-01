<?php

namespace App\Incidentes\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Identidad\Models\Cliente;
use App\Incidentes\Acciones\GuardarAcuerdoDeServicio;
use App\Incidentes\Consultas\PlazosDeLaEmpresa;
use App\Incidentes\Http\Requests\AcuerdoDeServicioRequest;
use App\Incidentes\Http\Resources\AcuerdoDeServicioResource;

class AcuerdoDeServicioController extends Controller
{
    public function show(Cliente $cliente, PlazosDeLaEmpresa $plazosDeLaEmpresa): AcuerdoDeServicioResource
    {
        return new AcuerdoDeServicioResource($plazosDeLaEmpresa->de($cliente->id));
    }

    public function update(AcuerdoDeServicioRequest $request, Cliente $cliente, GuardarAcuerdoDeServicio $guardar): AcuerdoDeServicioResource
    {
        $acuerdo = $guardar->ejecutar($request->user(), $cliente, $request->boolean('horario_laboral'), $request->plazos());

        return new AcuerdoDeServicioResource($acuerdo->aPlazos());
    }
}
