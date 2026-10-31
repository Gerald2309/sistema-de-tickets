<?php

namespace App\Identidad\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Identidad\Acciones\AsignarRolAUsuario;
use App\Identidad\Acciones\CrearUsuario;
use App\Identidad\Acciones\ModificarUsuario;
use App\Identidad\Consultas\ListarUsuarios;
use App\Identidad\Http\Requests\AsignarRolRequest;
use App\Identidad\Http\Requests\CrearUsuarioRequest;
use App\Identidad\Http\Requests\ListarUsuariosRequest;
use App\Identidad\Http\Requests\ModificarUsuarioRequest;
use App\Identidad\Http\Resources\UsuarioResource;
use App\Identidad\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class UsuarioController extends Controller
{
    public function index(ListarUsuariosRequest $request, ListarUsuarios $listarUsuarios): AnonymousResourceCollection
    {
        return UsuarioResource::collection($listarUsuarios->ejecutar($request->filtros(), $request->porPagina()));
    }

    public function store(CrearUsuarioRequest $request, CrearUsuario $crearUsuario): JsonResponse
    {
        $usuario = $crearUsuario->ejecutar($request->user(), $request->datos(), $request->ip());

        return (new UsuarioResource($usuario))->response()->setStatusCode(Response::HTTP_CREATED);
    }

    public function show(User $usuario): UsuarioResource
    {
        return new UsuarioResource($usuario->load(['rol', 'cliente']));
    }

    public function update(ModificarUsuarioRequest $request, User $usuario, ModificarUsuario $modificarUsuario): UsuarioResource
    {
        return new UsuarioResource($modificarUsuario->ejecutar($request->user(), $usuario, $request->cambios(), $request->ip()));
    }

    public function asignarRol(AsignarRolRequest $request, User $usuario, AsignarRolAUsuario $asignarRolAUsuario): UsuarioResource
    {
        return new UsuarioResource($asignarRolAUsuario->ejecutar($request->user(), $usuario, $request->integer('rol_id'), $request->ip()));
    }
}
