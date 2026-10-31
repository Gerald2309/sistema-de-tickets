<?php

namespace App\Incidentes\Http\Requests;

use App\Incidentes\Models\Incidente;

class ActualizarIncidenteRequest extends IncidenteRequest
{
    // Permiso por campo: editar, cambiar el estado y reasignar son capacidades distintas.
    public function authorize(): bool
    {
        /** @var Incidente $incidente */
        $incidente = $this->route('incidente');

        return $this->alcance()->puedeModificar(array_keys($this->all()), $incidente->usuario_creador_id);
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return $this->reglasComunes('sometimes') + $this->reglasDeEstado();
    }
}
