<?php

namespace App\Identidad\Http\Requests;

use App\Identidad\Http\Requests\Concerns\NormalizaCorreo;
use App\Identidad\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class ModificarUsuarioRequest extends FormRequest
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
        /** @var User $usuario */
        $usuario = $this->route('usuario');

        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'email' => ['sometimes', 'required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($usuario)],
            'activo' => ['sometimes', 'required', 'boolean'],
            // Restablecer la contraseña de otra persona; la propia se cambia con la contraseña actual.
            'password' => [
                'sometimes',
                'required',
                'string',
                Rule::prohibitedIf($usuario->is($this->user())),
                Password::defaults(),
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return ['password.prohibited' => trans('identidad.password_propia')];
    }

    /**
     * @return array{name?: string, email?: string, activo?: bool, password?: string}
     */
    public function cambios(): array
    {
        $cambios = $this->safe()->only(['name', 'email', 'password']);

        if ($this->has('activo')) {
            $cambios['activo'] = $this->boolean('activo');
        }

        return $cambios;
    }
}
