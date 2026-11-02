<?php

namespace App\Identidad\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Laravel\Sanctum\PersonalAccessToken;

/**
 * @property PersonalAccessToken $resource
 */
class SesionResource extends JsonResource
{
    /**
     * Una sesión abierta de la persona: desde dónde y cuándo. Nunca el token.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $sesion = $this->resource;

        return [
            'id' => $sesion->id,
            'ip' => $sesion->getAttribute('ip'),
            'agente' => $sesion->getAttribute('agente'),
            'iniciada_en' => $sesion->created_at?->toIso8601ZuluString(),
            'ultimo_uso_en' => $sesion->last_used_at?->toIso8601ZuluString(),
            'actual' => $sesion->is($request->user()?->currentAccessToken()),
        ];
    }
}
