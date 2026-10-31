<?php

namespace App\Identidad\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

// Crear y renombrar un rol reciben lo mismo: un nombre que no esté en uso por otro rol.
class NombreDeRolRequest extends FormRequest
{
    // Los permisos los comprueba la ruta (->can('administrar_roles')).
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            'nombre' => ['required', 'string', 'max:100', Rule::unique('roles', 'nombre')->ignore($this->route('rol'))],
        ];
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->input('nombre'))) {
            $this->merge(['nombre' => trim($this->input('nombre'))]);
        }
    }
}
