<?php

namespace App\Identidad\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Notifications\DatabaseNotification;

/**
 * Un aviso de la campanita. Si es sobre un incidente, trae su id para abrirlo.
 *
 * @property DatabaseNotification $resource
 */
class AvisoPersonalResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $aviso = $this->resource;

        return [
            'id' => $aviso->id,
            'texto' => $aviso->data['texto'] ?? '',
            'incidente_id' => $aviso->data['incidente_id'] ?? null,
            'leido' => $aviso->read_at !== null,
            'created_at' => $aviso->created_at?->copy()->utc()->toIso8601ZuluString(),
        ];
    }
}
