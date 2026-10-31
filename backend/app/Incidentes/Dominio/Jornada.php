<?php

namespace App\Incidentes\Dominio;

// La agenda de «Mi día»: de qué hora a qué hora se planea, en tramos de media hora, y cómo se busca un hueco.
// Las horas van en minutos del día local (600 son las 10:00).
final class Jornada
{
    public const PASO = 30;

    // Se puede planear desde temprano, aunque la vista muestre la jornada habitual.
    public const INICIO = 6 * 60;

    public const FIN = 18 * 60;

    public const DURACION_MAXIMA = 3 * 60;

    public const DURACION_PREDETERMINADA = 60;

    public const DURACION_DE_NOTA = 30;

    // Cuántos incidentes llena «Sugerir mi día»: lo habitual en una jornada.
    public const CAPACIDAD_HABITUAL = 5;

    /**
     * El primer inicio libre desde `$desde` (redondeado al tramo siguiente) para esa duración, o null si ya no
     * cabe ese día.
     *
     * @param  list<array{int, int}>  $ocupados  tramos [inicio, fin) ya planeados ese día
     */
    public static function huecoLibre(array $ocupados, int $desde, int $duracion): ?int
    {
        $inicio = max(self::INICIO, (int) (ceil($desde / self::PASO) * self::PASO));

        for (; $inicio + $duracion <= self::FIN; $inicio += self::PASO) {
            if (self::cabe($ocupados, $inicio, $duracion)) {
                return $inicio;
            }
        }

        return null;
    }

    /**
     * Si ese tramo cae dentro de la jornada y no choca con ninguno de los ocupados.
     *
     * @param  list<array{int, int}>  $ocupados
     */
    public static function cabe(array $ocupados, int $inicio, int $duracion): bool
    {
        if ($inicio < self::INICIO || $inicio + $duracion > self::FIN) {
            return false;
        }

        foreach ($ocupados as [$desde, $hasta]) {
            if ($inicio < $hasta && $desde < $inicio + $duracion) {
                return false;
            }
        }

        return true;
    }
}
