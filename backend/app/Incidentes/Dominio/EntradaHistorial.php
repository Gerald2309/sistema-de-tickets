<?php

namespace App\Incidentes\Dominio;

// Una fila del historial. Los constructores con nombre son la única forma de crearla, así que las
// combinaciones inválidas (un campo en una creación, una modificación sin campo) no existen.
final readonly class EntradaHistorial
{
    private function __construct(
        public EventoHistorial $evento,
        public ?CampoHistorial $campo = null,
        public ?string $valorAnterior = null,
        public ?string $valorNuevo = null,
    ) {}

    public static function creacion(): self
    {
        return new self(EventoHistorial::Creacion);
    }

    public static function borrado(): self
    {
        return new self(EventoHistorial::Borrado);
    }

    public static function modificacion(CampoHistorial $campo, ?string $valorAnterior, ?string $valorNuevo): self
    {
        return new self(EventoHistorial::Modificacion, $campo, $valorAnterior, $valorNuevo);
    }
}
