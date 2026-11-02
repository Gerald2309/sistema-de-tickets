<?php

namespace App\Incidentes\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Incidentes\Http\Resources\CatalogosResource;

class CatalogoController extends Controller
{
    /**
     * Catálogos: estados, prioridades y categorías
     */
    public function __invoke(): CatalogosResource
    {
        return new CatalogosResource(null);
    }
}
