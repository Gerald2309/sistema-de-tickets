<?php

namespace App\Dominio;

// Declarada en orden lógico: el ENUM de MySQL ordena por posición, no alfabéticamente.
enum Prioridad: string
{
    case Baja = 'baja';
    case Media = 'media';
    case Alta = 'alta';
    case Critica = 'critica';
}
