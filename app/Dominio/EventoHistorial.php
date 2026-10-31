<?php

namespace App\Dominio;

enum EventoHistorial: string
{
    case Creacion = 'creacion';
    case Modificacion = 'modificacion';
    case Borrado = 'borrado';
}
