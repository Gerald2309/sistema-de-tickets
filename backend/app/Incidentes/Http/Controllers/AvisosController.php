<?php

namespace App\Incidentes\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Incidentes\Consultas\DetectarIncidentesMasivos;
use App\Incidentes\Http\Resources\AvisoResource;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Date;

class AvisosController extends Controller
{
    public function __invoke(DetectarIncidentesMasivos $detectarIncidentesMasivos): AnonymousResourceCollection
    {
        return AvisoResource::collection($detectarIncidentesMasivos->ejecutar(Date::now()));
    }
}
