<?php

namespace App\Incidentes\Dominio;

// Las palabras que más se repiten entre varios incidentes: el síntoma común que se muestra en un aviso.
final class PalabrasRepetidas
{
    /**
     * Se cuenta en cuántos textos aparece cada palabra, no cuántas veces: una palabra repetida diez veces
     * en un solo texto cuenta una. Solo entran las que están en al menos `$minimoDeTextos` textos, sin
     * palabras muy comunes ni de menos de `$longitudMinima` letras. Las más frecuentes primero; si
     * empatan, por orden alfabético, para que el resultado sea siempre el mismo.
     *
     * @param  list<string>  $textos
     * @return list<string>
     */
    public static function desde(array $textos, int $longitudMinima, int $maximo, int $minimoDeTextos = 2): array
    {
        $apariciones = [];

        foreach ($textos as $texto) {
            // `desde` ya devuelve cada palabra una sola vez por texto.
            foreach (TerminosDeBusqueda::desde($texto, $longitudMinima, PHP_INT_MAX, sinPalabrasVacias: true) as $palabra) {
                $apariciones[$palabra] = ($apariciones[$palabra] ?? 0) + 1;
            }
        }

        $frecuentes = array_filter($apariciones, fn (int $textosConLaPalabra) => $textosConLaPalabra >= $minimoDeTextos);

        // Las claves numéricas ("691") llegan como enteros: se tratan como texto para ordenar y devolver.
        uksort($frecuentes, fn ($a, $b) => $frecuentes[$b] <=> $frecuentes[$a] ?: strcmp((string) $a, (string) $b));

        return array_map('strval', array_slice(array_keys($frecuentes), 0, $maximo));
    }
}
