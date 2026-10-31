<?php

namespace App\Identidad\Http\Resources;

use App\Identidad\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Una persona en un selector: sin correo (mínima exposición de datos). El rol solo va en la lista para
 * asignar, que es de personal de soporte, para agruparla por nivel.
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
            'rol' => $this->whenLoaded('rol', fn () => $this->resource->rol->nombre),
        ];
    }
}
