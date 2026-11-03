<?php

namespace App\Automatizaciones\Dominio;

// Lo que pone en marcha una automatización.
enum Disparador: string
{
    case Creacion = 'creacion';
}
