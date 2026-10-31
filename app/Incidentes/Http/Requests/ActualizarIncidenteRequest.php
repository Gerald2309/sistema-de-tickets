<?php

namespace App\Incidentes\Http\Requests;

use App\Incidentes\Dominio\Estado;
use Illuminate\Validation\Rule;

class ActualizarIncidenteRequest extends IncidenteRequest
{
    // Permiso por campo: editar, cambiar el estado y reasignar son capacidades distintas.
    public function authorize(): bool
    {
        return $this->alcance()->puedeModificar(array_keys($this->all()), $this->route('incidente')->usuario_creador_id);
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return $this->reglasComunes('sometimes') + [
            'estado' => ['sometimes', Rule::enum(Estado::class)],
            'solucion' => ['sometimes', 'required', 'string', 'max:10000'],
        ];
    }
}
