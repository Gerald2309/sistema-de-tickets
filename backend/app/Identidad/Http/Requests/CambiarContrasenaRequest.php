<?php

namespace App\Identidad\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class CambiarContrasenaRequest extends FormRequest
{
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
            'password_actual' => ['required', 'string', 'max:256'],
            'password' => ['required', 'string', 'different:password_actual', Password::defaults()],
        ];
    }
}
