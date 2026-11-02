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
            'cliente' => $usuario->cliente ? ['id' => $usuario->cliente->id, 'nombre' => $usuario->cliente->nombre] : null,
            'permisos' => $permisos->claves(),
            // Mientras sea verdadero, la API solo atiende lo necesario para cambiar la contraseña.
            'debe_cambiar_contrasena' => $usuario->debe_cambiar_contrasena,
            // El inicio anterior al de esta sesión, para notar uno que no hizo. Nulo en el primero.
            'inicio_anterior' => $usuario->inicio_anterior_en === null ? null : [
                'en' => $usuario->inicio_anterior_en->toIso8601ZuluString(),
                'ip' => $usuario->inicio_anterior_ip,
            ],
        ];
    }
}
