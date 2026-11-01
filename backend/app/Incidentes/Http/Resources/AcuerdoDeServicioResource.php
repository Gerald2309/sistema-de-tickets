<?php

namespace App\Incidentes\Http\Resources;

use App\Incidentes\Dominio\PlazosDeServicio;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property PlazosDeServicio $resource
 */
class AcuerdoDeServicioResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'horario_laboral' => $this->resource->horarioLaboral,
            'plazos' => $this->resource->plazos,
        ];
    }
}
