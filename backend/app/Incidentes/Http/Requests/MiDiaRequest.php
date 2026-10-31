<?php

namespace App\Incidentes\Http\Requests;

use App\Incidentes\Dominio\Jornada;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Base de las peticiones de «Mi día». Las fechas son días locales de la persona (AAAA-MM-DD) y las horas,
 * minutos de ese día en tramos de media hora: es su agenda, no un instante.
 */
abstract class MiDiaRequest extends FormRequest
{
    protected const FECHA = 'date_format:Y-m-d';

    /**
     * @return list<string>
     */
    protected static function reglasDeInicio(): array
    {
        return ['integer', 'multiple_of:'.Jornada::PASO, 'between:'.Jornada::INICIO.','.(Jornada::FIN - Jornada::PASO)];
    }

    /**
     * @return list<string>
     */
    protected static function reglasDeDuracion(): array
    {
        return ['integer', 'multiple_of:'.Jornada::PASO, 'between:'.Jornada::PASO.','.Jornada::DURACION_MAXIMA];
    }

    protected function dia(string $campo): CarbonImmutable
    {
        return CarbonImmutable::parse((string) $this->validated($campo))->startOfDay();
    }
}
