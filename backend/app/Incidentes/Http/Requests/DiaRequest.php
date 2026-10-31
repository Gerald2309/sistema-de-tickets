<?php

namespace App\Incidentes\Http\Requests;

use Carbon\CarbonImmutable;

// Lo que depende de qué día es hoy para la persona (su día local): pendientes, pizarra y «Sugerir mi día».
class DiaRequest extends MiDiaRequest
{
    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'fecha' => ['required', self::FECHA],
            // Solo para sugerir: desde qué minuto del día buscar huecos (la hora actual de la persona).
            'desde' => ['sometimes', 'integer', 'between:0,1440'],
        ];
    }

    public function fecha(): CarbonImmutable
    {
        return $this->dia('fecha');
    }

    public function desde(): int
    {
        return (int) $this->validated('desde', 0);
    }
}
