<?php

namespace App\Automatizaciones\Http\Requests;

use App\Automatizaciones\Http\Requests\Concerns\ValidaElDisparo;
use App\Automatizaciones\Http\Requests\Concerns\ValidaLosPasos;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

// Crear o reemplazar una automatización completa: nombre, si está activa, cuándo se dispara y sus pasos.
class GuardarAutomatizacionRequest extends FormRequest
{
    use ValidaElDisparo, ValidaLosPasos;

    // Los permisos los comprueba la ruta (->can('administrar_automatizaciones')).
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->input('nombre'))) {
            $this->merge(['nombre' => $this->string('nombre')->squish()->toString()]);
        }
    }

    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'nombre' => ['required', 'string', 'max:120'],
            'activa' => ['required', 'boolean'],
            'disparador' => ['required', 'string'],
            'espera_minutos' => ['nullable', 'integer'],
            'porcentaje' => ['nullable', 'integer'],
            'plazo' => ['nullable', 'string'],
            'pasos' => ['present', 'array'],
        ];
    }

    /**
     * @return list<callable(Validator): void>
     */
    public function after(): array
    {
        return [function (Validator $validador): void {
            if ($validador->errors()->isNotEmpty()) {
                return;
            }

            $this->validarDisparo($validador);
            $this->validarPasos($validador);

            if ($validador->errors()->isEmpty() && $this->boolean('activa') && ! $this->flujo()->tieneAcciones()) {
                $validador->errors()->add('pasos', trans('automatizaciones.sin_acciones'));
            }
        }];
    }
}
