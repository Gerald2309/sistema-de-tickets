<?php

namespace App\Automatizaciones\Http\Resources;

use App\Automatizaciones\Models\Automatizacion;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Date;

/**
 * Se entrega con su resumen de uso (Automatizacion::conResumen).
 *
 * @property Automatizacion $resource
 */
class AutomatizacionResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $automatizacion = $this->resource;
        $ultima = $automatizacion->getAttribute('ejecuciones_max_created_at');

        return [
            'id' => $automatizacion->id,
            'nombre' => $automatizacion->nombre,
            'activa' => $automatizacion->activa,
            ...$automatizacion->disparo()->aArreglo(),
            'pasos' => $automatizacion->flujo()->aArreglo(),
            // Lo que hace queda a nombre de esta persona.
            'autor' => ['id' => $automatizacion->autor->id, 'name' => $automatizacion->autor->name],
            'aplicaciones' => (int) $automatizacion->getAttribute('ejecuciones_count'),
            'aplicaciones_con_error' => (int) $automatizacion->getAttribute('ejecuciones_con_error_count'),
            'ultima_aplicacion_en' => $ultima === null ? null : Date::parse($ultima)->utc()->toIso8601ZuluString(),
            'updated_at' => $automatizacion->updated_at?->copy()->utc()->toIso8601ZuluString(),
        ];
    }
}
