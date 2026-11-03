<?php

namespace App\Automatizaciones\Dominio;

// Los pasos de una automatización como un árbol: unas acciones en orden y, al final, si acaso, una
// condición que abre dos caminos, cada uno con su propio flujo.
final readonly class Flujo
{
    /**
     * @param  list<Accion>  $acciones
     */
    public function __construct(public array $acciones = [], public ?Condicion $condicion = null) {}

    /**
     * Las acciones que le tocan a ese incidente, en orden: las de este tramo y las del camino que tome.
     *
     * @return list<Accion>
     */
    public function accionesPara(Hechos $hechos): array
    {
        return [...$this->acciones, ...($this->condicion?->caminoPara($hechos)->accionesPara($hechos) ?? [])];
    }

    /**
     * Los pasos tal como se guardan: lo que `LectorDeFlujo::leer` vuelve a convertir en este mismo flujo.
     *
     * @return list<array<string, mixed>>
     */
    public function aArreglo(): array
    {
        $pasos = array_map(fn (Accion $accion) => $accion->aArreglo(), $this->acciones);

        return $this->condicion === null ? $pasos : [...$pasos, $this->condicion->aArreglo()];
    }
}
