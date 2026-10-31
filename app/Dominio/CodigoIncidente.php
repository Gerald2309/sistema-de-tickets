<?php

namespace App\Dominio;

// Código legible del ticket, derivado del id (no se guarda): INC-000042.
final class CodigoIncidente
{
    private const PREFIJO = 'INC-';

    private const DIGITOS_MINIMOS = 6;

    private const DIGITOS_MAXIMOS = 18;

    public static function deId(int $id): string
    {
        return self::PREFIJO.str_pad((string) $id, self::DIGITOS_MINIMOS, '0', STR_PAD_LEFT);
    }

    /**
     * Devuelve el id de un código válido, o null si el texto no es un código.
     */
    public static function aId(string $codigo): ?int
    {
        if (preg_match('/^INC-(\d{1,'.self::DIGITOS_MAXIMOS.'})$/i', trim($codigo), $coincidencia) !== 1) {
            return null;
        }

        $id = (int) $coincidencia[1];

        return $id > 0 ? $id : null;
    }
}
