<?php

namespace App\Identidad\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Identidad\Acciones\CerrarSesion;
use App\Identidad\Acciones\IniciarSesion;
use App\Identidad\Http\Requests\LoginRequest;
use App\Identidad\Http\Resources\TokenResource;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Date;

class AuthController extends Controller
{
    /**
     * Iniciar sesión
     *
     * Entrega el token con que se autentican las demás peticiones.
     *
     * @unauthenticated
     */
    public function login(LoginRequest $request, IniciarSesion $iniciarSesion): TokenResource
    {
        $token = $iniciarSesion->ejecutar(
            $request->validated('email'),
            $request->validated('password'),
            $request->ip(),
            $request->userAgent(),
            Date::now(),
        );

        return new TokenResource($token);
    }

    public function logout(Request $request, CerrarSesion $cerrarSesion): Response
    {
        $cerrarSesion->ejecutar($request->user());

        return response()->noContent();
    }
}
