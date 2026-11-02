<?php

namespace App\Identidad\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Identidad\Acciones\CerrarSesionesPropias;
use App\Identidad\Http\Resources\SesionResource;
use App\Identidad\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

// Las sesiones abiertas de quien pregunta. Solo las propias: la consulta parte siempre de su usuario.
class SesionController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        /** @var User $usuario la ruta exige sesión (auth:sanctum) */
        $usuario = $request->user();

        return SesionResource::collection($usuario->tokens()->latest('last_used_at')->latest('id')->get());
    }

    public function destroy(Request $request, int $sesion, CerrarSesionesPropias $cerrarSesiones): Response
    {
        $cerrarSesiones->una($request->user(), $sesion);

        return response()->noContent();
    }

    public function destroyLasDemas(Request $request, CerrarSesionesPropias $cerrarSesiones): Response
    {
        $cerrarSesiones->lasDemas($request->user());

        return response()->noContent();
    }
}
