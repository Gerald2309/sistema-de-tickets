<?php

namespace App\Auditoria\Http\Resources;

use App\Auditoria\Models\Auditoria;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property Auditoria $resource
 */
class AuditoriaResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $registro = $this->resource;

        return [
            'id' => $registro->id,
            'accion' => $registro->accion->value,
            'usuario' => $registro->usuario_id ? ['id' => $registro->usuario_id, 'name' => $registro->usuario_nombre] : null,
            'usuario_afectado' => $registro->usuario_afectado_id ? ['id' => $registro->usuario_afectado_id, 'name' => $registro->usuario_afectado_nombre] : null,
            'detalle' => $registro->detalle,
            'ip' => $registro->ip,
            'created_at' => $registro->created_at->copy()->utc()->toIso8601ZuluString(),
        ];
    }
}
