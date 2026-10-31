<?php

namespace App\Incidentes\Http\Resources;

use App\Incidentes\Dominio\AvisoDeIncidenteMasivo;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property AvisoDeIncidenteMasivo $resource
 */
class AvisoResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $aviso = $this->resource;

        return [
            'categoria' => $aviso->categoria->value,
            'incidentes' => $aviso->incidentes,
            'personas' => $aviso->personas,
            'palabras' => $aviso->palabras,
            'desde' => $aviso->desde->format('Y-m-d\TH:i:s\Z'),
        ];
    }
}
