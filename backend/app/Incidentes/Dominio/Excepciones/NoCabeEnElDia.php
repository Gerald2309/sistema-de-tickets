<?php

namespace App\Incidentes\Dominio\Excepciones;

use DomainException;

final class NoCabeEnElDia extends DomainException
{
    public function __construct()
    {
        parent::__construct('No cabe en ese día: choca con otra actividad o se sale de la jornada.');
    }
}
