<?php

namespace App\Incidentes\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

// Los días de la tendencia son los de la persona: llega su desfase respecto de UTC, en minutos.
class IndicadoresRequest extends FormRequest
{
    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            // Las zonas horarias van de UTC−12 a UTC+14.
            'desfase' => ['sometimes', 'integer', 'between:-720,840'],
        ];
    }

    public function desfase(): int
    {
        return (int) $this->validated('desfase', 0);
    }
}
