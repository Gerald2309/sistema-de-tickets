<?php

namespace App\Identidad\Http\Requests;

use App\Identidad\Consultas\ListarUsuarios;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ListarUsuariosRequest extends FormRequest
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
        return [
            'q' => ['nullable', 'string', 'max:100'],
            'rol_id' => ['nullable', 'integer', Rule::exists('roles', 'id')],
            'activo' => ['nullable', 'boolean'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'between:1,'.ListarUsuarios::POR_PAGINA_MAXIMO],
        ];
    }

    /**
     * @return array{q: string|null, rol_id: int|null, activo: bool|null}
     */
    public function filtros(): array
    {
        return [
            'q' => $this->filled('q') ? $this->string('q')->trim()->toString() : null,
            'rol_id' => $this->filled('rol_id') ? $this->integer('rol_id') : null,
            'activo' => $this->filled('activo') ? $this->boolean('activo') : null,
        ];
    }

    public function porPagina(): int
    {
        return $this->integer('per_page', ListarUsuarios::POR_PAGINA_PREDETERMINADO);
    }
}
