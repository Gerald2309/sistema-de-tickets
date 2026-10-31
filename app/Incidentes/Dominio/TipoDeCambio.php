<?php

namespace App\Incidentes\Dominio;

// Qué le pasó a un incidente, para el aviso de tiempo real (F-12).
enum TipoDeCambio: string
{
    case Creado = 'creado';
    case Actualizado = 'actualizado';
    case Eliminado = 'eliminado';

    // Si en una misma petición ocurren cambios de tipos distintos, el aviso conjunto es «actualizado».
    public function combinadoCon(self $otro): self
    {
        return $this === $otro ? $this : self::Actualizado;
    }
}
