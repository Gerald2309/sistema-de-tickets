<?php

namespace App\Incidentes\Dominio\Excepciones;

use DomainException;

final class ConversacionCerrada extends DomainException
{
    public function __construct()
    {
        parent::__construct('El incidente está cerrado: su conversación ya no admite mensajes.');
    }
}
