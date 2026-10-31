<?php

namespace App\Identidad\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Identidad\Dominio\ClavePermiso;
use Illuminate\Http\JsonResponse;

class PermisoController extends Controller
{
    // El catálogo es fijo y sale del enum: no hace falta consultar la base de datos.
    public function __invoke(): JsonResponse
    {
        return response()->json(['data' => array_map(
            fn (ClavePermiso $permiso) => ['clave' => $permiso->value],
            ClavePermiso::cases(),
        )]);
    }
}
