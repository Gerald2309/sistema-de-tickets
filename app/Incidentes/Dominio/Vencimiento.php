<?php

namespace App\Incidentes\Dominio;

use DateTimeInterface;

// "Vencido": fecha de vencimiento anterior al instante actual y estado sin resolver (D-04).
// El instante actual se recibe como argumento: la regla no depende del reloj.
final class Vencimiento
{
    /**
     * Estados en los que un incidente puede vencer. También los usa la consulta SQL.
     *
     * @return list<Estado>
     */
    public static function estadosQueVencen(): array
    {
        return [Estado::Abierto, Estado::EnProgreso];
    }

    public static function estaVencido(DateTimeInterface $fechaVencimiento, Estado $estado, DateTimeInterface $ahora): bool
    {
        return in_array($estado, self::estadosQueVencen(), true) && $fechaVencimiento < $ahora;
    }
}
