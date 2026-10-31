<?php

namespace App\Identidad\Http\Requests\Concerns;

use Illuminate\Support\Str;

// El correo se guarda y se busca en minúsculas y sin espacios en los extremos.
trait NormalizaCorreo
{
    protected function prepareForValidation(): void
    {
        if (is_string($this->input('email'))) {
            $this->merge(['email' => Str::lower(trim($this->input('email')))]);
        }
    }
}
