<?php

namespace App\Incidentes\Http\Resources;

use App\Incidentes\Models\AdjuntoMensaje;
use App\Incidentes\Models\MensajeIncidente;
use App\Incidentes\Policies\IncidentePolicy;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property MensajeIncidente $resource
 */
class MensajeResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $mensaje = $this->resource;

        return [
            'id' => $mensaje->id,
            'cuerpo' => $mensaje->cuerpo,
            'interno' => $mensaje->interno,
            'autor' => ['id' => $mensaje->autor->id, 'name' => $mensaje->autor->name],
            // El proceso que escribió a nombre del autor (una automatización), o null si lo escribió él. Es un dato
            // interno: solo lo ve quien lee las notas internas.
            'origen' => $this->when(IncidentePolicy::alcanceDe($request->user())->puedeLeerNotasInternas(), $mensaje->origen),
            'adjuntos' => $mensaje->adjuntos->map(fn (AdjuntoMensaje $adjunto) => [
                'id' => $adjunto->id,
                'nombre' => $adjunto->nombre,
                'tipo' => $adjunto->tipo,
                'bytes' => $adjunto->bytes,
            ])->all(),
            'created_at' => $mensaje->created_at->copy()->utc()->toIso8601ZuluString(),
        ];
    }
}
