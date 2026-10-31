<?php

namespace App\Incidentes\Http\Requests;

class CrearIncidenteRequest extends IncidenteRequest
{
    // Asignar al crear exige poder reasignar.
    public function authorize(): bool
    {
        return $this->input('usuario_asignado_id') === null
            || $this->alcance()->puedeReasignar($this->user()->id);
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return $this->reglasComunes('required');
    }
}
