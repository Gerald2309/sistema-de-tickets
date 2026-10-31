<?php

namespace App\Identidad\Http\Resources;

use App\Identidad\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Una persona en un selector: sin correo ni rol (mínima exposición de datos).
 *
 * @property User $resource
 */
class PersonaResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id,
            'name' => $this->resource->name,
            'activo' => $this->resource->activo,
        ];
    }
}
