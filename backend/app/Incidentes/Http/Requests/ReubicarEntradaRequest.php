<?php

namespace App\Incidentes\Http\Requests;

use App\Incidentes\Models\EntradaDelPlan;
use Carbon\CarbonImmutable;

// Mover una entrada (a otro día u hora) o cambiarle la duración. Lo que no se envía se conserva.
class ReubicarEntradaRequest extends MiDiaRequest
{
    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'fecha' => ['sometimes', self::FECHA],
            'inicio' => ['required_with:fecha', 'required_without:duracion', ...self::reglasDeInicio()],
            'duracion' => ['sometimes', ...self::reglasDeDuracion()],
        ];
    }

    public function fecha(EntradaDelPlan $entrada): CarbonImmutable
    {
        return $this->has('fecha') ? $this->dia('fecha') : $entrada->fecha->toImmutable();
    }

    public function inicio(EntradaDelPlan $entrada): int
    {
        return $this->has('inicio') ? (int) $this->validated('inicio') : $entrada->inicio;
    }

    public function duracion(EntradaDelPlan $entrada): int
    {
        return $this->has('duracion') ? (int) $this->validated('duracion') : $entrada->duracion;
    }

    // Moverla busca el primer hueco desde la hora pedida; estirarla o acortarla en su lugar es exacto.
    public function esExacto(): bool
    {
        return ! $this->has('inicio');
    }
}
