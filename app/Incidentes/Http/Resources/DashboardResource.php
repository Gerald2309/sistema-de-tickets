<?php

namespace App\Incidentes\Http\Resources;

use App\Incidentes\Dominio\ResumenDeIncidentes;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property ResumenDeIncidentes $resource
 */
class DashboardResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'total' => $this->resource->total,
            'por_estado' => $this->resource->porEstado,
            'por_prioridad' => $this->resource->porPrioridad,
            'vencidos' => $this->resource->vencidos,
        ];
    }
}
