<?php

namespace App\Incidentes\Http\Requests;

use App\Incidentes\Models\NotaPersonal;
use Illuminate\Foundation\Http\FormRequest;

// Crear una nota exige el texto; al cambiarla, cada campo es opcional.
class NotaPersonalRequest extends FormRequest
{
    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        $alCrear = $this->isMethod('post');

        return [
            'texto' => [$alCrear ? 'required' : 'sometimes', 'string', 'max:'.NotaPersonal::MAXIMO_TEXTO],
            'hecha' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * @return array{texto?: string, hecha?: bool}
     */
    public function campos(): array
    {
        $campos = $this->safe()->only(['texto', 'hecha']);
        if (array_key_exists('texto', $campos)) {
            $campos['texto'] = trim($campos['texto']);
        }
        if (array_key_exists('hecha', $campos)) {
            $campos['hecha'] = $this->boolean('hecha');
        }

        return $campos;
    }
}
