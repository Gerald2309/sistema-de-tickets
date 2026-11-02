<?php

namespace App\Identidad\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Identidad\Acciones\AsignarPermisosARol;
use App\Identidad\Acciones\CrearRol;
use App\Identidad\Acciones\EliminarRol;
use App\Identidad\Acciones\RenombrarRol;
use App\Identidad\Consultas\ListarRoles;
use App\Identidad\Http\Requests\AsignarPermisosRequest;
use App\Identidad\Http\Requests\NombreDeRolRequest;
use App\Identidad\Http\Resources\RolResource;
use App\Identidad\Models\Rol;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class RolController extends Controller
{
    /**
     * Listar roles
     */
    public function index(ListarRoles $listarRoles): AnonymousResourceCollection
    {
        return RolResource::collection($listarRoles->ejecutar());
    }

    /**
     * Ver un rol
     */
    public function show(Rol $rol): RolResource
    {
        return new RolResource($rol->load('permisos')->loadCount('usuarios'));
    }

    /**
     * Crear un rol
     */
    public function store(NombreDeRolRequest $request, CrearRol $crearRol): JsonResponse
    {
        $rol = $crearRol->ejecutar($request->user(), $request->validated('nombre'), $request->ip());

        return (new RolResource($rol))->response()->setStatusCode(Response::HTTP_CREATED);
    }

    /**
     * Renombrar un rol
     */
    public function update(NombreDeRolRequest $request, Rol $rol, RenombrarRol $renombrarRol): RolResource
    {
        return new RolResource($renombrarRol->ejecutar($request->user(), $rol, $request->validated('nombre'), $request->ip()));
    }

    /**
     * Eliminar un rol
     */
    public function destroy(Request $request, Rol $rol, EliminarRol $eliminarRol): Response
    {
        $eliminarRol->ejecutar($request->user(), $rol, $request->ip());

        return response()->noContent();
    }

    /**
     * Asignar permisos a un rol
     */
    public function asignarPermisos(AsignarPermisosRequest $request, Rol $rol, AsignarPermisosARol $asignarPermisosARol): RolResource
    {
        return new RolResource($asignarPermisosARol->ejecutar($request->user(), $rol, $request->claves(), $request->ip()));
    }
}
