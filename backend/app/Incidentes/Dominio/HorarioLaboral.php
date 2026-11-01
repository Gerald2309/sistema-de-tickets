<?php

namespace App\Incidentes\Dominio;

use Carbon\CarbonImmutable;

// Lunes a viernes de 8:00 a 18:00, en la zona horaria del instante que recibe. Los festivos no se descuentan.
final class HorarioLaboral
{
    private const HORA_DE_APERTURA = 8;

    private const HORA_DE_CIERRE = 18;

    // Suma minutos de trabajo: lo que no cabe hoy sigue en la siguiente apertura.
    public static function sumar(CarbonImmutable $desde, int $minutos): CarbonImmutable
    {
        $momento = self::enHorario($desde);

        while (true) {
            $cierre = $momento->setTime(self::HORA_DE_CIERRE, 0);
            $disponibles = (int) $momento->diffInMinutes($cierre);

            if ($minutos <= $disponibles) {
                return $momento->addMinutes($minutos);
            }

            $minutos -= $disponibles;
            $momento = self::enHorario($cierre);
        }
    }

    // El mismo instante si ya es horario laboral; si no, la siguiente apertura.
    private static function enHorario(CarbonImmutable $momento): CarbonImmutable
    {
        if ($momento->hour < self::HORA_DE_APERTURA && ! $momento->isWeekend()) {
            return $momento->setTime(self::HORA_DE_APERTURA, 0);
        }

        if ($momento->hour < self::HORA_DE_CIERRE && ! $momento->isWeekend()) {
            return $momento;
        }

        $siguiente = $momento->addDay()->setTime(self::HORA_DE_APERTURA, 0);

        while ($siguiente->isWeekend()) {
            $siguiente = $siguiente->addDay();
        }

        return $siguiente;
    }
}
