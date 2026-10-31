<?php

namespace App\Identidad\Dominio\Excepciones;

use DomainException;

final class UltimoAdministrador extends DomainException
{
    public function __construct()
    {
        parent::__construct('Debe quedar al menos un usuario activo que pueda administrar roles y otro que pueda administrar usuarios.');
    }
}
