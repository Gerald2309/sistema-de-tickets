<?php

namespace App\Incidentes\Http\Requests;

use App\Incidentes\Models\Incidente;

class ActualizarIncidenteRequest extends IncidenteRequest
{
    // Permiso por campo: editar, cambiar el estado y reasignar son capacidades distintas, y dependen de
    // quién atiende el incidente.
    public function authorize(): bool
    {
        /** @var Incidente $incidente */
        $incidente = $this->route('incidente');

        return $this->alcance()->puedeModificar($this->all(), $incidente->participantes());
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return $this->reglasComunes('sometimes') + $this->reglasDeEstado();
    }
}
