<?php

namespace App\Automatizaciones\Http\Controllers;

use App\Automatizaciones\Acciones\CambiarEstadoDeAutomatizacion;
use App\Automatizaciones\Acciones\EliminarAutomatizacion;
use App\Automatizaciones\Acciones\GuardarAutomatizacion;
use App\Automatizaciones\Consultas\SimularAutomatizacion;
use App\Automatizaciones\Http\Requests\CambiarEstadoRequest;
use App\Automatizaciones\Http\Requests\GuardarAutomatizacionRequest;
use App\Automatizaciones\Http\Requests\ProbarAutomatizacionRequest;
use App\Automatizaciones\Http\Resources\AutomatizacionResource;
use App\Automatizaciones\Models\Automatizacion;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Date;

class AutomatizacionController extends Controller
{
    /**
     * Listar automatizaciones
     */
    public function index(): AnonymousResourceCollection
    {
        return AutomatizacionResource::collection(Automatizacion::query()->conResumen()->orderBy('nombre')->get());
    }

    /**
     * Ver una automatización
     */
    public function show(Automatizacion $automatizacion): AutomatizacionResource
    {
        return $this->conResumen($automatizacion);
    }

    /**
     * Crear una automatización
     */
    public function store(GuardarAutomatizacionRequest $request, GuardarAutomatizacion $guardarAutomatizacion): JsonResponse
    {
        $creada = $this->guardar($request, $guardarAutomatizacion, null);

        return $this->conResumen($creada)->response()->setStatusCode(Response::HTTP_CREATED);
    }

    /**
     * Reemplazar una automatización
     */
    public function update(GuardarAutomatizacionRequest $request, Automatizacion $automatizacion, GuardarAutomatizacion $guardarAutomatizacion): AutomatizacionResource
    {
        return $this->conResumen($this->guardar($request, $guardarAutomatizacion, $automatizacion));
    }

    /**
     * Encender o apagar una automatización
     */
    public function cambiarEstado(CambiarEstadoRequest $request, Automatizacion $automatizacion, CambiarEstadoDeAutomatizacion $cambiarEstado): AutomatizacionResource
    {
        return $this->conResumen($cambiarEstado->ejecutar($request->user(), $automatizacion, $request->boolean('activa'), $request->ip()));
    }

    /**
     * Eliminar una automatización
     */
    public function destroy(Request $request, Automatizacion $automatizacion, EliminarAutomatizacion $eliminarAutomatizacion): Response
    {
        $eliminarAutomatizacion->ejecutar($request->user(), $automatizacion, $request->ip());

        return response()->noContent();
    }

    /**
     * Probar unos pasos con los incidentes recientes
     *
     * No cambia nada: dice por qué camino habría salido cada incidente creado en los últimos 30 días y cuántos
     * habrían recibido acciones.
     */
    public function probar(ProbarAutomatizacionRequest $request, SimularAutomatizacion $simularAutomatizacion): JsonResponse
    {
        return response()->json(['data' => $simularAutomatizacion->ejecutar($request->flujo(), Date::now()->toImmutable())]);
    }

    private function guardar(GuardarAutomatizacionRequest $request, GuardarAutomatizacion $guardarAutomatizacion, ?Automatizacion $automatizacion): Automatizacion
    {
        return $guardarAutomatizacion->ejecutar(
            $request->user(),
            $automatizacion,
            $request->string('nombre')->toString(),
            $request->boolean('activa'),
            $request->disparo(),
            $request->flujo(),
            $request->ip(),
        );
    }

    private function conResumen(Automatizacion $automatizacion): AutomatizacionResource
    {
        return new AutomatizacionResource(Automatizacion::query()->conResumen()->findOrFail($automatizacion->id));
    }
}
