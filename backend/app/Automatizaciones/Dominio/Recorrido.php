<?php

namespace App\Automatizaciones\Dominio;

// Por dónde pasó un incidente en un flujo: qué respondió cada condición y qué acciones le tocaron.
final readonly class Recorrido
{
    /**
     * @param  list<array{campo: CampoDeCondicion, cumple: bool}>  $decisiones  en el orden en que se tomaron
     * @param  list<Accion>  $acciones
     */
    public function __construct(public array $decisiones, public array $acciones) {}
}
