<?php

namespace App\Incidentes\Dominio;

// Declarada en orden lógico: el ENUM de MySQL ordena por posición, no alfabéticamente.
enum Prioridad: string
{
    case Baja = 'baja';
    case Media = 'media';
    case Alta = 'alta';
    case Critica = 'critica';

    // La misma que asigna la base de datos cuando la petición no la indica.
    public const PREDETERMINADA = self::Media;
}
