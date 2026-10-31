<?php

namespace App\Incidentes\Http\Requests;

use App\Incidentes\Dominio\AlcanceIncidentes;
use App\Incidentes\Dominio\Categoria;
use App\Incidentes\Dominio\Prioridad;
use App\Incidentes\Policies\IncidentePolicy;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;

// Reglas comunes de crear y actualizar. Solo los campos listados llegan a la acción: el creador,
// el estado inicial y cualquier otro dato enviado se descartan.
abstract class IncidenteRequest extends FormRequest
{
    // ISO 8601 con zona horaria: con desfase (2026-10-30T15:00:00-05:00) o en UTC con la letra Z (2026-10-30T20:00:00Z).
    public const FORMATOS_FECHA = 'Y-m-d\TH:i:sP,Y-m-d\TH:i:s\Z';

    /**
     * @param  string  $presencia  'required' al crear; 'sometimes' al actualizar.
     * @return array<string, list<mixed>>
     */
    protected function reglasComunes(string $presencia): array
    {
        return [
            'titulo' => [$presencia, 'string', 'max:255'],
            'descripcion' => [$presencia, 'string', 'max:10000'],
            'prioridad' => ['sometimes', Rule::enum(Prioridad::class)],
            'categoria' => ['sometimes', Rule::enum(Categoria::class)],
            'fecha_vencimiento' => [$presencia, 'date_format:'.self::FORMATOS_FECHA],
            'usuario_asignado_id' => [
                'sometimes',
                'nullable',
                'integer',
                Rule::exists('users', 'id')->where('activo', true),
            ],
        ];
    }

    /**
     * Datos validados, con el vencimiento ya en UTC: un valor con otra zona se guardaría con su hora local.
     *
     * @return array<string, mixed>
     */
    public function datos(): array
    {
        $datos = $this->validated();

        if (isset($datos['fecha_vencimiento'])) {
            $datos['fecha_vencimiento'] = Carbon::parse($datos['fecha_vencimiento'])->utc();
        }

        return $datos;
    }

    protected function alcance(): AlcanceIncidentes
    {
        return IncidentePolicy::alcanceDe($this->user());
    }
}
