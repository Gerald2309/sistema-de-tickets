<?php

namespace App\Incidentes\Http\Requests;

use App\Incidentes\Consultas\IncidentesDelLote;
use Illuminate\Foundation\Http\FormRequest;

class EliminarIncidentesEnLoteRequest extends FormRequest
{
    // Los permisos los comprueba la ruta y, incidente por incidente, la acción.
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            'ids' => ['required', 'array', 'min:1', 'max:'.IncidentesDelLote::MAXIMO],
            'ids.*' => ['integer', 'min:1', 'distinct'],
        ];
    }

    /**
     * @return list<int>
     */
    public function ids(): array
    {
        return array_map('intval', $this->validated('ids'));
    }
}
