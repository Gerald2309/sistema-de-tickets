<?php

namespace App\Automatizaciones\Dominio\Excepciones;

use DomainException;

// Un paso de la automatización no tiene sentido. `ruta` señala el dato exacto dentro de los pasos
// enviados (por ejemplo «pasos.1.si.0.usuario_id»), para mostrar el error junto a ese bloque.
final class FlujoInvalido extends DomainException
{
    public function __construct(public readonly string $ruta, string $motivo)
    {
        parent::__construct($motivo);
    }
}
