<?php

namespace App\Automatizaciones\Http\Requests;

use App\Automatizaciones\Models\Automatizacion;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

// Encender o apagar una automatización desde la lista, sin reenviar sus pasos.
class CambiarEstadoRequest extends FormRequest
{
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
        return ['activa' => ['required', 'boolean']];
    }

    /**
     * @return list<callable(Validator): void>
     */
    public function after(): array
    {
        return [function (Validator $validador): void {
            /** @var Automatizacion $automatizacion */
            $automatizacion = $this->route('automatizacion');

            if ($validador->errors()->isEmpty() && $this->boolean('activa') && ! $automatizacion->flujo()->tieneAcciones()) {
                $validador->errors()->add('activa', trans('automatizaciones.sin_acciones'));
            }
        }];
    }
}
