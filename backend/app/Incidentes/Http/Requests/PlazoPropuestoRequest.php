<?php

namespace App\Incidentes\Http\Requests;

use App\Incidentes\Dominio\Prioridad;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PlazoPropuestoRequest extends FormRequest
{
    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return ['prioridad' => ['required', Rule::enum(Prioridad::class)]];
    }

    public function prioridad(): Prioridad
    {
        return Prioridad::from($this->string('prioridad')->toString());
    }
}
