<?php

namespace App\Identidad\Http\Resources;

use App\Identidad\Models\Permiso;
use App\Identidad\Models\Rol;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property Rol $resource
 */
class RolResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $rol = $this->resource;

        return [
            'id' => $rol->id,
            'nombre' => $rol->nombre,
            'permisos' => $rol->permisos->map(fn (Permiso $permiso) => $permiso->clave->value)->sort()->values()->all(),
            'usuarios' => $rol->usuarios_count,
        ];
    }
}
