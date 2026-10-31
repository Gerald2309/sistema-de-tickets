<?php

namespace App\Incidentes\Dominio\Excepciones;

use DomainException;

final class NoSePuedeTomar extends DomainException
{
    public static function yaTieneAsignado(): self
    {
        return new self('Este incidente ya tiene asignada a una persona.');
    }

    public static function estaCerrado(): self
    {
        return new self('Un incidente cerrado ya no se puede tomar.');
    }
}
