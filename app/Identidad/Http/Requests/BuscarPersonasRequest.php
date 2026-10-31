<?php

namespace App\Identidad\Http\Requests;

use App\Identidad\Consultas\BuscarPersonas;
use Illuminate\Foundation\Http\FormRequest;

class BuscarPersonasRequest extends FormRequest
{
    // Los permisos los comprueba la ruta (->can('consultar_personas')).
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
            // Solo por nombre: buscar por correo permitiría comprobar si un correo existe.
            'q' => ['nullable', 'string', 'max:100'],
            'ids' => ['nullable', 'array', 'max:'.BuscarPersonas::MAXIMO],
            'ids.*' => ['integer', 'min:1', 'distinct'],
            'incluir_inactivas' => ['nullable', 'boolean'],
        ];
    }

    public function texto(): ?string
    {
        return $this->filled('q') ? $this->string('q')->trim()->toString() : null;
    }

    /**
     * @return list<int>
     */
    public function ids(): array
    {
        return array_values(array_map('intval', (array) $this->input('ids', [])));
    }

    public function incluirInactivas(): bool
    {
        return $this->boolean('incluir_inactivas');
    }
}
