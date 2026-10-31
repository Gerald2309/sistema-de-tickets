<?php

namespace App\Identidad\Http\Requests;

use App\Identidad\Consultas\ListarClientes;
use Illuminate\Foundation\Http\FormRequest;

class ListarClientesRequest extends FormRequest
{
    // Los permisos los comprueba la ruta (->can('consultar_clientes')).
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
            'activo' => ['nullable', 'boolean'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'between:1,'.ListarClientes::POR_PAGINA_MAXIMO],
        ];
    }

    /**
     * @return array{q: string|null, activo: bool|null}
     */
    public function filtros(): array
    {
        return [
            'q' => $this->filled('q') ? $this->string('q')->trim()->toString() : null,
            'activo' => $this->filled('activo') ? $this->boolean('activo') : null,
        ];
    }

    public function porPagina(): int
    {
        return $this->integer('per_page', ListarClientes::POR_PAGINA_PREDETERMINADO);
    }
}
