<?php

namespace App\Identidad\Dominio\Excepciones;

use DomainException;

final class RolEnUso extends DomainException
{
    public function __construct()
    {
        parent::__construct('El rol tiene usuarios asignados: hay que cambiarles el rol antes de eliminarlo.');
    }
}
