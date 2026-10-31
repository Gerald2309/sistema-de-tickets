<?php

namespace App\Dominio\Excepciones;

use App\Dominio\Estado;
use DomainException;

final class TransicionInvalida extends DomainException
{
    public function __construct(public readonly Estado $desde, public readonly Estado $hacia)
    {
        $permitidos = array_map(fn (Estado $estado) => $estado->value, $desde->siguientes());

        parent::__construct(sprintf(
            'No se puede pasar de «%s» a «%s». Estados permitidos: %s.',
            $desde->value,
            $hacia->value,
            $permitidos === [] ? 'ninguno (estado final)' : implode(', ', $permitidos),
        ));
    }
}
