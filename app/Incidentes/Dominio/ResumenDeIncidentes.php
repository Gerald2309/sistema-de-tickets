<?php

namespace App\Incidentes\Dominio;

// Conteos del dashboard. El total y los dos agrupados salen de las mismas filas, así que siempre cuadran.
final readonly class ResumenDeIncidentes
{
    /**
     * @param  array<string, int>  $porEstado  una entrada por cada estado, aunque sea 0
     * @param  array<string, int>  $porPrioridad  una entrada por cada prioridad, aunque sea 0
     */
    private function __construct(
        public int $total,
        public array $porEstado,
        public array $porPrioridad,
        public int $vencidos,
    ) {}

    /**
     * @param  iterable<array{estado: string, prioridad: string, cantidad: int}>  $grupos  conteo por estado y prioridad
     */
    public static function desde(iterable $grupos, int $vencidos): self
    {
        $porEstado = array_fill_keys(array_column(Estado::cases(), 'value'), 0);
        $porPrioridad = array_fill_keys(array_column(Prioridad::cases(), 'value'), 0);

        foreach ($grupos as $grupo) {
            $porEstado[$grupo['estado']] += $grupo['cantidad'];
            $porPrioridad[$grupo['prioridad']] += $grupo['cantidad'];
        }

        return new self(array_sum($porEstado), $porEstado, $porPrioridad, $vencidos);
    }
}
