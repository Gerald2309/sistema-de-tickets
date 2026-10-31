<?php

namespace App\Identidad\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AsignarRolRequest extends FormRequest
{
    // Los permisos los comprueba la ruta (->can('administrar_usuarios')).
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return ['rol_id' => ['required', 'integer', Rule::exists('roles', 'id')]];
    }
}
