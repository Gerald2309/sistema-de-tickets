<?php

namespace App\Incidentes\Http\Resources;

use App\Incidentes\Dominio\CodigoIncidente;
use App\Incidentes\Dominio\Vencimiento;
use App\Incidentes\Models\Incidente;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Date;

/**
 * Lo que «Mi día» muestra de un incidente (consultado con `paraElPlanDe`): sin personas ni textos largos.
 *
 * @property Incidente $resource
 */
class IncidenteDelPlanResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $incidente = $this->resource;

        return [
            'id' => $incidente->id,
            'codigo' => CodigoIncidente::deId($incidente->id),
            'titulo' => $incidente->titulo,
            'prioridad' => $incidente->prioridad->value,
            'estado' => $incidente->estado->value,
            'vencido' => Vencimiento::estaVencido($incidente->fecha_vencimiento, $incidente->estado, Date::now()),
            'fecha_vencimiento' => $incidente->fecha_vencimiento->copy()->utc()->toIso8601ZuluString(),
            'bloqueo' => $incidente->getAttribute('bloqueo'),
        ];
    }
}
