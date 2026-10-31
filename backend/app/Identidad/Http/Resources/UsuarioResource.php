<?php

namespace App\Identidad\Http\Resources;

use App\Identidad\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property User $resource
 */
class UsuarioResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $usuario = $this->resource;

        return [
            'id' => $usuario->id,
            'name' => $usuario->name,
            'email' => $usuario->email,
            'activo' => $usuario->activo,
            'rol' => ['id' => $usuario->rol->id, 'nombre' => $usuario->rol->nombre],
            'cliente' => $usuario->cliente ? ['id' => $usuario->cliente->id, 'nombre' => $usuario->cliente->nombre] : null,
            'created_at' => $usuario->created_at?->copy()->utc()->toIso8601ZuluString(),
        ];
    }
}
