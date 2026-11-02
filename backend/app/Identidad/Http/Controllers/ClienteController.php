<?php

namespace App\Identidad\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Identidad\Acciones\CrearCliente;
use App\Identidad\Acciones\ModificarCliente;
use App\Identidad\Consultas\ListarClientes;
use App\Identidad\Http\Requests\ClienteRequest;
use App\Identidad\Http\Requests\ListarClientesRequest;
use App\Identidad\Http\Resources\ClienteResource;
use App\Identidad\Models\Cliente;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class ClienteController extends Controller
{
    /**
     * Listar empresas
     */
    public function index(ListarClientesRequest $request, ListarClientes $listarClientes): AnonymousResourceCollection
    {
        return ClienteResource::collection($listarClientes->ejecutar($request->filtros(), $request->porPagina()));
    }

    /**
     * Crear una empresa
     */
    public function store(ClienteRequest $request, CrearCliente $crearCliente): JsonResponse
    {
        $cliente = $crearCliente->ejecutar($request->user(), $request->string('nombre')->toString(), $request->ip());

        return (new ClienteResource($cliente))->response()->setStatusCode(Response::HTTP_CREATED);
    }

    /**
     * Modificar una empresa
     */
    public function update(ClienteRequest $request, Cliente $cliente, ModificarCliente $modificarCliente): ClienteResource
    {
        return new ClienteResource($modificarCliente->ejecutar($request->user(), $cliente, $request->cambios(), $request->ip()));
    }
}
