<?php

namespace App\Identidad\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Identidad\Acciones\CambiarContrasena;
use App\Identidad\Http\Requests\CambiarContrasenaRequest;
use App\Identidad\Http\Resources\PerfilResource;
use App\Identidad\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PerfilController extends Controller
{
    public function mostrar(Request $request): PerfilResource
    {
        /** @var User $usuario la ruta exige sesión (auth:sanctum) */
        $usuario = $request->user();

        return new PerfilResource($usuario->loadMissing('cliente'));
    }

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
