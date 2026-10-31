<?php

namespace App\Identidad\Http\Resources;

use App\Identidad\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property User $resource
 */
class PerfilResource extends JsonResource
{
    /**
     * Quién es la persona y qué puede hacer: lo que un cliente necesita para mostrar u ocultar opciones.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $usuario = $this->resource;
        $permisos = $usuario->permisos();

        return [
            'id' => $usuario->id,
            'name' => $usuario->name,
            'email' => $usuario->email,
            'rol' => ['id' => $usuario->rol->id, 'nombre' => $usuario->rol->nombre],
            'permisos' => $permisos->claves(),
        ];
    }
}
