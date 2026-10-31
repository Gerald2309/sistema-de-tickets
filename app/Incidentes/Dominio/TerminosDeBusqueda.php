<?php

namespace App\Incidentes\Dominio;

// Convierte el texto que escribe la persona en las palabras que se buscan. Solo conserva letras y
// números, así que los operadores del motor de búsqueda (+ - < > ( ) ~ * " @) nunca llegan a él.
final class TerminosDeBusqueda
{
    /**
     * Palabras en minúsculas, sin repetir, en el orden en que aparecen. Se descartan las de menos de
     * `$longitudMinima` letras, porque el índice de texto no las guarda, y se conservan como máximo
     * `$maximo`.
     *
     * @return list<string>
     */
    public static function desde(string $texto, int $longitudMinima, int $maximo): array
    {
        $palabras = preg_split('/[^\p{L}\p{N}_]+/u', mb_strtolower($texto), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $terminos = [];

        foreach ($palabras as $palabra) {
            if (mb_strlen($palabra) >= $longitudMinima && ! in_array($palabra, $terminos, true)) {
                $terminos[] = $palabra;
            }

            if (count($terminos) === $maximo) {
                break;
            }
        }

        return $terminos;
    }
}
