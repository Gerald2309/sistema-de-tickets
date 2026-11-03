<?php

namespace App\Automatizaciones\Http\Requests;

use App\Automatizaciones\Http\Requests\Concerns\ValidaElDisparo;
use App\Automatizaciones\Http\Requests\Concerns\ValidaLosPasos;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

// Probar unos pasos, guardados o no, con los incidentes recientes. Con su disparador, dice además a cuántos
// incidentes les toca ahora mismo.
class ProbarAutomatizacionRequest extends FormRequest
{
    use ValidaElDisparo, ValidaLosPasos;

    // Los permisos los comprueba la ruta (->can('administrar_automatizaciones')).
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'pasos' => ['present', 'array'],
            'disparador' => ['sometimes', 'string'],
            'espera_minutos' => ['nullable', 'integer'],
            'porcentaje' => ['nullable', 'integer'],
            'plazo' => ['nullable', 'string'],
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

            if ($this->conDisparo()) {
                $this->validarDisparo($validador);
            }

            $this->validarPasos($validador);
        }];
    }

    public function conDisparo(): bool
    {
        return $this->filled('disparador');
    }
}
