<?php

namespace App\Identidad\Http\Resources;

use App\Identidad\Models\Cliente;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property Cliente $resource
 */
class ClienteResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id,
            'nombre' => $this->resource->nombre,
            'activo' => $this->resource->activo,
            // Personas de la empresa.
            'usuarios' => $this->resource->usuarios_count,
        ];
    }
}
