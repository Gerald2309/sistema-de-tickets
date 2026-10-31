<?php

namespace App\Incidentes\Dominio;

// Lista blanca de campos por los que se puede ordenar el listado. El título queda fuera: ordenar
// por él exigiría un índice que ninguna otra consulta justifica. `creado` es la fecha de creación.
enum CampoOrden: string
{
    case Id = 'id';
    case Prioridad = 'prioridad';
    case Estado = 'estado';
    case FechaVencimiento = 'fecha_vencimiento';
    case Creado = 'creado';

    public function columna(): string
    {
        return match ($this) {
            self::Creado => 'created_at',
            default => $this->value,
        };
    }
}
