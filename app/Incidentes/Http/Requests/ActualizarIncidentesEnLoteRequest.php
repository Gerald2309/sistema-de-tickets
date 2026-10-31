<?php

namespace App\Incidentes\Http\Requests;

use App\Incidentes\Consultas\IncidentesDelLote;
use Illuminate\Support\Arr;

class ActualizarIncidentesEnLoteRequest extends IncidenteRequest
{
    // En lote solo se cambian estos campos: los que tienen sentido aplicar igual a varios incidentes.
    private const CAMPOS_COMUNES = ['prioridad', 'categoria', 'usuario_asignado_id'];

    // Los permisos los comprueba la ruta y, incidente por incidente, la acción.
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<mixed>>
     */
    public function rules(): array
    {
        $reglas = Arr::only($this->reglasComunes('sometimes'), self::CAMPOS_COMUNES) + $this->reglasDeEstado();

        return [
            'ids' => ['required', 'array', 'min:1', 'max:'.IncidentesDelLote::MAXIMO],
            'ids.*' => ['integer', 'min:1', 'distinct'],
            'cambios' => ['required', 'array', 'min:1'],
        ] + collect($reglas)->mapWithKeys(fn (array $regla, string $campo) => ["cambios.{$campo}" => $regla])->all();
    }

    /**
     * @return list<int>
     */
    public function ids(): array
    {
        return array_values(array_map('intval', $this->validated('ids')));
    }

    /**
     * @return array<string, mixed>
     */
    public function cambios(): array
    {
        return $this->validated('cambios');
    }
}
