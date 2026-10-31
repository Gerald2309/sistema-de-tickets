<?php

namespace App\Incidentes\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PorPlanearRequest extends FormRequest
{
    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'buscar' => ['nullable', 'string', 'max:100'],
            'cursor' => ['nullable', 'string'],
        ];
    }

    public function buscar(): ?string
    {
        $texto = trim((string) $this->validated('buscar'));

        return $texto === '' ? null : $texto;
    }
}
