<?php

namespace App\Incidentes\Http\Resources;

use App\Incidentes\Models\HistorialIncidente;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property HistorialIncidente $resource
 */
class HistorialResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $entrada = $this->resource;

        return [
            'id' => $entrada->id,
            'evento' => $entrada->evento->value,
            'campo' => $entrada->campo?->value,
            'valor_anterior' => $entrada->valor_anterior,
            'valor_nuevo' => $entrada->valor_nuevo,
            'autor' => ['id' => $entrada->autor->id, 'name' => $entrada->autor->name],
            'created_at' => $entrada->created_at->copy()->utc()->toIso8601ZuluString(),
        ];
    }
}
