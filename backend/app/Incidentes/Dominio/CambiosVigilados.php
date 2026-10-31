<?php

namespace App\Incidentes\Dominio;

// Decide qué cambios de un incidente quedan en el historial: los campos vigilados (CampoHistorial)
// cuyo valor es distinto.
final class CambiosVigilados
{
    /**
     * Una entrada por cada campo vigilado que cambió, en el orden de CampoHistorial.
     * Los valores van como texto, igual que se guardan, y las claves son las de CampoHistorial.
     *
     * @param  array<string, string|null>  $antes
     * @param  array<string, string|null>  $despues
     * @return list<EntradaHistorial>
     */
    public static function modificaciones(array $antes, array $despues): array
    {
        $entradas = [];

        foreach (CampoHistorial::cases() as $campo) {
            $clave = $campo->value;

            if (! array_key_exists($clave, $antes) || ! array_key_exists($clave, $despues)) {
                continue;
            }

            if ($antes[$clave] !== $despues[$clave]) {
                $entradas[] = EntradaHistorial::modificacion($campo, $antes[$clave], $despues[$clave]);
            }
        }

        return $entradas;
    }
}
