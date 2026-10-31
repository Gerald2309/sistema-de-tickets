<?php

namespace App\Incidentes\Http\Resources;

use App\Incidentes\Dominio\Categoria;
use App\Incidentes\Dominio\Estado;
use App\Incidentes\Dominio\Prioridad;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

// Valores válidos de estado, prioridad y categoría, y los pasos permitidos desde cada estado,
// para que el frontend no los repita.
class CatalogosResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'estados' => array_map(fn (Estado $estado) => [
                'valor' => $estado->value,
                'siguientes' => array_map(fn (Estado $siguiente) => $siguiente->value, $estado->siguientes()),
            ], Estado::cases()),
            'prioridades' => array_map(fn (Prioridad $prioridad) => $prioridad->value, Prioridad::cases()),
            'categorias' => array_map(fn (Categoria $categoria) => $categoria->value, Categoria::cases()),
        ];
    }
}
