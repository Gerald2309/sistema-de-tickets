<?php

namespace App\Identidad\Http\Requests;

use App\Identidad\Models\Cliente;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

// Alta (nombre obligatorio) y modificación (nombre o estado activo) de una empresa cliente.
class ClienteRequest extends FormRequest
{
    // Los permisos los comprueba la ruta (->can('administrar_usuarios')).
    public function authorize(): bool
    {
        return true;
    }

    // El nombre se guarda sin espacios de más, para que «Banco  Aliado» y «Banco Aliado» no sean dos empresas.
    protected function prepareForValidation(): void
    {
        if ($this->has('nombre') && is_string($this->input('nombre'))) {
            $this->merge(['nombre' => $this->string('nombre')->squish()->toString()]);
        }
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        /** @var Cliente|null $cliente */
        $cliente = $this->route('cliente');
        $alCrear = $cliente === null;

        return [
            'nombre' => [$alCrear ? 'required' : 'sometimes', 'required', 'string', 'max:150', Rule::unique('clientes', 'nombre')->ignore($cliente)],
            'activo' => $alCrear ? ['prohibited'] : ['sometimes', 'required', 'boolean'],
        ];
    }

    /**
     * @return array{nombre?: string, activo?: bool}
     */
    public function cambios(): array
    {
        $cambios = $this->safe()->only(['nombre']);

        if ($this->has('activo')) {
            $cambios['activo'] = $this->boolean('activo');
        }

        return $cambios;
    }
}
