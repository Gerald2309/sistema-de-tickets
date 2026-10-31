<?php

namespace App\Incidentes\Dominio;

enum Direccion: string
{
    case Ascendente = 'asc';
    case Descendente = 'desc';
}
