<?php

namespace App\Automatizaciones\Dominio;

use Generator;

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
        return $this->recorrer($hechos)->acciones;
    }

    public function recorrer(Hechos $hechos): Recorrido
    {
        if ($this->condicion === null) {
            return new Recorrido([], $this->acciones);
        }

        $cumple = $this->condicion->seCumple($hechos);
        $resto = ($cumple ? $this->condicion->si : $this->condicion->no)->recorrer($hechos);

        return new Recorrido(
            [['campo' => $this->condicion->campo, 'cumple' => $cumple], ...$resto->decisiones],
            [...$this->acciones, ...$resto->acciones],
        );
    }

    /**
     * Todos los pasos de todos los caminos, cada uno con su ruta («pasos.1.si.0»), la misma con la que
     * LectorDeFlujo señala un error.
     *
     * @return Generator<string, Accion|Condicion>
     */
    public function pasos(string $ruta = 'pasos'): Generator
    {
        foreach ($this->acciones as $i => $accion) {
            yield "{$ruta}.{$i}" => $accion;
        }

        if ($this->condicion !== null) {
            $rutaDeLaCondicion = $ruta.'.'.count($this->acciones);

            yield $rutaDeLaCondicion => $this->condicion;
            yield from $this->condicion->si->pasos("{$rutaDeLaCondicion}.si");
            yield from $this->condicion->no->pasos("{$rutaDeLaCondicion}.no");
        }
    }

    // Si hace algo en alguno de sus caminos: activar una que no hace nada no tiene sentido.
    public function tieneAcciones(): bool
    {
        foreach ($this->pasos() as $paso) {
            if ($paso instanceof Accion) {
                return true;
            }
        }

        return false;
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
