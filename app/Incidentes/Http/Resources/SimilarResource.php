<?php

namespace App\Incidentes\Http\Resources;

use App\Incidentes\Dominio\CodigoIncidente;
use App\Incidentes\Models\Incidente;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

// Lo que hace falta para aprovechar un caso parecido: qué era y cómo se resolvió.
/**
 * @property Incidente $resource
 */
class SimilarResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $incidente = $this->resource;

        return [
            'id' => $incidente->id,
            'codigo' => CodigoIncidente::deId($incidente->id),
            'titulo' => $incidente->titulo,
            'categoria' => $incidente->categoria->value,
            'estado' => $incidente->estado->value,
            'solucion' => $incidente->solucion,
        ];
    }
}
