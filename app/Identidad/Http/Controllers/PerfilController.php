<?php

namespace App\Identidad\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Identidad\Acciones\CambiarContrasena;
use App\Identidad\Http\Requests\CambiarContrasenaRequest;
use Illuminate\Http\JsonResponse;

class PerfilController extends Controller
{
    public function cambiarContrasena(CambiarContrasenaRequest $request, CambiarContrasena $cambiarContrasena): JsonResponse
    {
        $cambiarContrasena->ejecutar(
            $request->user(),
            $request->validated('password_actual'),
            $request->validated('password'),
            $request->ip(),
        );

        return response()->json(['mensaje' => 'La contraseña se actualizó.']);
    }
}
