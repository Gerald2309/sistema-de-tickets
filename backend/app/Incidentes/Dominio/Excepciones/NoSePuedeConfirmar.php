<?php

namespace App\Incidentes\Dominio\Excepciones;

use DomainException;

final class NoSePuedeConfirmar extends DomainException
{
    public function __construct()
    {
        parent::__construct('Solo se confirma la solución de un incidente resuelto.');
    }
}
