<?php

namespace App\Identidad\Dominio\Excepciones;

use DomainException;

final class RolFueraDeAlcance extends DomainException
{
    public function __construct()
    {
        parent::__construct('No puede asignar un rol con permisos de administración que usted no tiene.');
    }
}
