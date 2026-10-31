<?php

namespace App\Incidentes\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BloqueoRequest extends FormRequest
{
    public const MAXIMO_MOTIVO = 120;

    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return ['motivo' => ['required', 'string', 'max:'.self::MAXIMO_MOTIVO]];
    }

    public function motivo(): string
    {
        return trim((string) $this->validated('motivo'));
    }
}
