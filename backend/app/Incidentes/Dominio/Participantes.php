<?php

namespace App\Incidentes\Dominio;

// Las personas de un incidente de las que dependen los permisos: quien lo reportó y quien lo atiende.
final readonly class Participantes
{
    public function __construct(
        public int $creadorId,
        public ?int $asignadoId,
    ) {}

    public function sinAsignar(): bool
    {
        return $this->asignadoId === null;
    }
}
