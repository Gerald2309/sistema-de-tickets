<?php

namespace App\Incidentes\Http\Resources;

use App\Incidentes\Models\EntradaDelPlan;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Una entrada de la agenda: el día, la hora y la duración (en minutos del día local) y qué es.
 *
 * @property EntradaDelPlan $resource
 */
class EntradaDelPlanResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $entrada = $this->resource;

        return [
            'id' => $entrada->id,
            'fecha' => $entrada->fecha->toDateString(),
            'inicio' => $entrada->inicio,
            'duracion' => $entrada->duracion,
            'incidente' => $entrada->incidente ? new IncidenteDelPlanResource($entrada->incidente) : null,
            'nota' => $entrada->nota ? new NotaPersonalResource($entrada->nota) : null,
        ];
    }
}
