<?php

namespace App\Incidentes\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Incidentes\Acciones\GuardarNotaPersonal;
use App\Incidentes\Consultas\NotasDeLaPizarra;
use App\Incidentes\Http\Requests\DiaRequest;
use App\Incidentes\Http\Requests\NotaPersonalRequest;
use App\Incidentes\Http\Resources\NotaPersonalResource;
use App\Incidentes\Models\NotaPersonal;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

// La pizarra de notas de «Mi día». Cada persona solo alcanza las suyas: las de otra responden 404.
class NotaPersonalController extends Controller
{
    public function index(DiaRequest $request, NotasDeLaPizarra $pizarra): AnonymousResourceCollection
    {
        return NotaPersonalResource::collection($pizarra->ejecutar($request->user(), $request->fecha()));
    }

    public function store(NotaPersonalRequest $request, GuardarNotaPersonal $guardar): JsonResponse
    {
        return (new NotaPersonalResource($guardar->ejecutar($request->user(), $request->campos())))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function update(NotaPersonalRequest $request, int $nota, GuardarNotaPersonal $guardar): NotaPersonalResource
    {
        return new NotaPersonalResource($guardar->ejecutar($request->user(), $request->campos(), $this->propia($request, $nota)));
    }

    public function destroy(Request $request, int $nota): Response
    {
        $this->propia($request, $nota)->delete();

        return response()->noContent();
    }

    private function propia(Request $request, int $id): NotaPersonal
    {
        return NotaPersonal::de($request->user()->id)->findOrFail($id);
    }
}
