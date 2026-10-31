<?php

namespace App\Incidentes\Dominio;

// Fecha sobre la que se aplica el rango del listado (por defecto, el vencimiento).
enum CampoFecha: string
{
    case Vencimiento = 'vencimiento';
    case Creacion = 'creacion';

    public function columna(): string
    {
        return match ($this) {
            self::Vencimiento => 'fecha_vencimiento',
            self::Creacion => 'created_at',
        };
    }
}
