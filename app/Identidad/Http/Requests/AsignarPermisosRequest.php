<?php

namespace App\Identidad\Http\Requests;

use App\Identidad\Dominio\ClavePermiso;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AsignarPermisosRequest extends FormRequest
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
            'permisos' => ['present', 'array'],
            'permisos.*' => ['distinct', Rule::enum(ClavePermiso::class)],
        ];
    }

    /**
     * @return list<ClavePermiso>
     */
    public function claves(): array
    {
        return array_map(ClavePermiso::from(...), $this->validated('permisos'));
    }
}
