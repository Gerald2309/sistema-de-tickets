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
            // Los textos largos no van en el listado (ListarIncidentes no los consulta).
            'descripcion' => $this->whenHas('descripcion'),
            'categoria' => $incidente->categoria->value,
            'prioridad' => $incidente->prioridad->value,
            'estado' => $incidente->estado->value,
            'estados_siguientes' => array_map(fn (Estado $estado) => $estado->value, $incidente->estado->siguientes()),
            'solucion' => $this->whenHas('solucion'),
            'calificacion' => $incidente->calificacion,
            // Solo en el listado y el detalle: depende de quién pregunta.
            'mensajes_sin_leer' => $this->whenHas('mensajes_sin_leer', fn () => (int) $incidente->getAttribute('mensajes_sin_leer')),
            'vencido' => Vencimiento::estaVencido($incidente->fecha_vencimiento, $incidente->estado, Date::now()),
            'fecha_vencimiento' => $incidente->fecha_vencimiento->copy()->utc()->toIso8601ZuluString(),
            // Solo en el detalle: ListarIncidentes no los consulta.
            'fecha_requerida' => $this->whenHas('fecha_requerida', fn () => $incidente->fecha_requerida?->copy()->utc()->toIso8601ZuluString()),
            'vence_respuesta_en' => $this->whenHas('vence_respuesta_en', fn () => $incidente->vence_respuesta_en?->copy()->utc()->toIso8601ZuluString()),
            'primera_respuesta_en' => $this->whenHas('primera_respuesta_en', fn () => $incidente->primera_respuesta_en?->copy()->utc()->toIso8601ZuluString()),
            'resuelto_en' => $this->whenHas('resuelto_en', fn () => $incidente->resuelto_en?->copy()->utc()->toIso8601ZuluString()),
            'creador' => ['id' => $incidente->creador->id, 'name' => $incidente->creador->name],
            'asignado' => $incidente->asignado
                ? ['id' => $incidente->asignado->id, 'name' => $incidente->asignado->name]
                : null,
            'cliente' => $incidente->cliente
                ? ['id' => $incidente->cliente->id, 'nombre' => $incidente->cliente->nombre]
                : null,
            'created_at' => $incidente->created_at?->copy()->utc()->toIso8601ZuluString(),
            'updated_at' => $incidente->updated_at?->copy()->utc()->toIso8601ZuluString(),
        ];
    }
}
