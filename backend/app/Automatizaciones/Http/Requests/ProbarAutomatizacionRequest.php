<?php

namespace App\Automatizaciones\Http\Requests;

use App\Automatizaciones\Http\Requests\Concerns\ValidaLosPasos;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

// Probar unos pasos, guardados o no, con los incidentes recientes.
class ProbarAutomatizacionRequest extends FormRequest
{
    use ValidaLosPasos;

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
        return ['pasos' => ['present', 'array']];
    }

    /**
     * @return list<callable(Validator): void>
     */
    public function after(): array
    {
        return [function (Validator $validador): void {
            if ($validador->errors()->isEmpty()) {
                $this->validarPasos($validador);
            }
        }];
    }
}
