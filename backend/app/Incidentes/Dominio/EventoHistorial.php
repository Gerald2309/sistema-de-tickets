<?php

namespace App\Incidentes\Dominio;

enum EventoHistorial: string
{
    case Creacion = 'creacion';
    case Modificacion = 'modificacion';
    case Borrado = 'borrado';
}
