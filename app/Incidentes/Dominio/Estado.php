<?php

namespace App\Incidentes\Dominio;

use App\Incidentes\Dominio\Excepciones\TransicionInvalida;

// Declarado en el orden del ciclo de vida: el ENUM de MySQL ordena por posición.
enum Estado: string
{
    case Abierto = 'abierto';
    case EnProgreso = 'en_progreso';
    case Resuelto = 'resuelto';
    case Cerrado = 'cerrado';

    /**
     * Estados a los que puede pasar desde este (ciclo de vida, RF-19). Cerrado es final.
     *
     * @return list<self>
     */
    public function siguientes(): array
    {
        return match ($this) {
            self::Abierto => [self::EnProgreso],
            self::EnProgreso => [self::Resuelto],
            self::Resuelto => [self::Cerrado, self::EnProgreso],
            self::Cerrado => [],
        };
    }

    public function puedePasarA(self $destino): bool
    {
        return in_array($destino, $this->siguientes(), true);
    }

    /**
     * @throws TransicionInvalida
     */
    public function pasarA(self $destino): self
    {
        return $this->puedePasarA($destino) ? $destino : throw new TransicionInvalida($this, $destino);
    }
}
