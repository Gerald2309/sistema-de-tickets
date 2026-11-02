<?php

namespace App\Incidentes\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Identidad\Models\User;
use App\Incidentes\Acciones\TraspasarIncidentesAbiertos;
use App\Incidentes\Http\Requests\TraspasoRequest;
use Illuminate\Http\JsonResponse;

// Antes de desactivar a alguien: cuántos incidentes sin resolver tiene asignados y a quién pasan.
class TraspasoController extends Controller
{
    public function __construct(private readonly TraspasarIncidentesAbiertos $traspasar) {}

    /**
     * Incidentes abiertos de un usuario
     */
    public function show(User $usuario): JsonResponse
    {
        return response()->json(['data' => ['total' => $this->traspasar->contar($usuario)]]);
    }

    /**
     * Traspasar los incidentes abiertos de un usuario
     */
    public function store(TraspasoRequest $request, User $usuario): JsonResponse
    {
        return response()->json(['data' => [
            'traspasados' => $this->traspasar->ejecutar($request->user(), $usuario, $request->destino()),
        ]]);
    }
}
