<?php

namespace App\Auditoria\Http\Controllers;

use App\Auditoria\Consultas\ListarAuditoria;
use App\Auditoria\Http\Requests\ListarAuditoriaRequest;
use App\Auditoria\Http\Resources\AuditoriaResource;
use App\Http\Controllers\Controller;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class AuditoriaController extends Controller
{
    public function __invoke(ListarAuditoriaRequest $request, ListarAuditoria $listarAuditoria): AnonymousResourceCollection
    {
        return AuditoriaResource::collection($listarAuditoria->ejecutar($request->filtros(), $request->porPagina()));
    }
}
