<?php

namespace App\Incidentes\Http\Resources;

use App\Incidentes\Dominio\CodigoIncidente;
use App\Incidentes\Models\NotaPersonal;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property NotaPersonal $resource
 */
class NotaPersonalResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $nota = $this->resource;

        return [
            'id' => $nota->id,
            'texto' => $nota->texto,
            'hecha' => $nota->hecha,
            // Solo si quien la escribió todavía ve ese incidente.
            'incidente' => $nota->incidente
                ? ['id' => $nota->incidente->id, 'codigo' => CodigoIncidente::deId($nota->incidente->id)]
                : null,
        ];
    }
}
