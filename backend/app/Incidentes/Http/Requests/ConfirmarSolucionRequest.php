<?php

namespace App\Incidentes\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ConfirmarSolucionRequest extends FormRequest
{
    public const MAXIMO_MOTIVO = 10000;

    // Los permisos los comprueba la ruta (->can('confirmarSolucion')).
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Si no quedó solucionado, el motivo es obligatorio: soporte necesita saber qué sigue pasando. La
     * calificación solo tiene sentido cuando sí quedó solucionado.
     *
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'solucionado' => ['required', 'boolean'],
            'motivo' => ['required_if_declined:solucionado', 'prohibited_if_accepted:solucionado', 'nullable', 'string', 'max:'.self::MAXIMO_MOTIVO],
            'calificacion' => ['prohibited_if_declined:solucionado', 'nullable', 'integer', 'between:1,5'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'motivo.required_if_declined' => trans('incidentes.motivo_obligatorio'),
        ];
    }

    public function solucionado(): bool
    {
        return $this->boolean('solucionado');
    }

    public function motivo(): ?string
    {
        return $this->filled('motivo') ? $this->string('motivo')->trim()->toString() : null;
    }

    public function calificacion(): ?int
    {
        return $this->filled('calificacion') ? $this->integer('calificacion') : null;
    }
}
