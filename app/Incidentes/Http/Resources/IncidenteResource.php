<?php

namespace App\Incidentes\Http\Resources;

use App\Incidentes\Dominio\CodigoIncidente;
use App\Incidentes\Dominio\Estado;
use App\Incidentes\Dominio\Vencimiento;
use App\Incidentes\Models\Incidente;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Date;

/**
 * @property Incidente $resource
 */
class IncidenteResource extends JsonResource
{
    public static $wrap = null;

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
            'descripcion' => $incidente->descripcion,
            'categoria' => $incidente->categoria->value,
            'prioridad' => $incidente->prioridad->value,
            'estado' => $incidente->estado->value,
            'estados_siguientes' => array_map(fn (Estado $estado) => $estado->value, $incidente->estado->siguientes()),
            'solucion' => $incidente->solucion,
            'vencido' => Vencimiento::estaVencido($incidente->fecha_vencimiento, $incidente->estado, Date::now()),
            'fecha_vencimiento' => $incidente->fecha_vencimiento->copy()->utc()->toIso8601ZuluString(),
            'creador' => ['id' => $incidente->creador->id, 'name' => $incidente->creador->name],
            'asignado' => $incidente->asignado
                ? ['id' => $incidente->asignado->id, 'name' => $incidente->asignado->name]
                : null,
            'created_at' => $incidente->created_at->copy()->utc()->toIso8601ZuluString(),
            'updated_at' => $incidente->updated_at->copy()->utc()->toIso8601ZuluString(),
        ];
    }
}
