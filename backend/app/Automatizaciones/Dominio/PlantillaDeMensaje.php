<?php

namespace App\Automatizaciones\Dominio;

// Texto con datos del incidente entre llaves: «Hola {nombre}, recibimos tu caso {número}». Solo admite las
// variables conocidas. El resultado es texto plano: quien lo muestre lo escapa como cualquier mensaje.
final class PlantillaDeMensaje
{
    public const VARIABLES = ['nombre', 'número', 'título', 'empresa', 'prioridad'];

    public const LONGITUD_MAXIMA = 5000;

    private const VARIABLE = '/\{([^{}]*)\}/u';

    /**
     * Variables escritas en el texto que no existen, sin repetir.
     *
     * @return list<string>
     */
    public static function desconocidas(string $texto): array
    {
        preg_match_all(self::VARIABLE, $texto, $variables);

        return array_values(array_unique(array_diff($variables[1], self::VARIABLES)));
    }

    /**
     * @param  array<string, string>  $valores  valor de cada variable; las que falten se dejan como están
     */
    public static function rellenar(string $texto, array $valores): string
    {
        return preg_replace_callback(self::VARIABLE, fn (array $variable) => $valores[$variable[1]] ?? $variable[0], $texto) ?? $texto;
    }
}
