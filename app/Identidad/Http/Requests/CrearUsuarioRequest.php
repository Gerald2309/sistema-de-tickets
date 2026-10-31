<?php

namespace App\Identidad\Http\Requests;

use App\Identidad\Http\Requests\Concerns\NormalizaCorreo;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class CrearUsuarioRequest extends FormRequest
{
    use NormalizaCorreo;

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
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')],
            'password' => ['required', 'string', Password::defaults()],
            'rol_id' => ['required', 'integer', Rule::exists('roles', 'id')],
        ];
    }

    /**
     * @return array{name: string, email: string, password: string, rol_id: int}
     */
    public function datos(): array
    {
        return [
            'name' => $this->string('name')->toString(),
            'email' => $this->string('email')->toString(),
            'password' => $this->string('password')->toString(),
            'rol_id' => $this->integer('rol_id'),
        ];
    }
}
