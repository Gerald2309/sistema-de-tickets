<?php

namespace App\Incidentes\Dominio;

// Conteos del dashboard. El total y los dos agrupados salen de las mismas filas, así que siempre cuadran;
// el total de vencidos es la suma de los vencidos por estado.
final readonly class ResumenDeIncidentes
{
    /**
     * @param  array<string, int>  $porEstado  una entrada por cada estado, aunque sea 0
     * @param  array<string, int>  $porPrioridad  una entrada por cada prioridad, aunque sea 0
     * @param  array<string, int>  $vencidosPorEstado  una entrada por cada estado que puede vencer, aunque sea 0
     */
    private function __construct(
        public int $total,
        public array $porEstado,
        public array $porPrioridad,
        public int $vencidos,
        public array $vencidosPorEstado,
    ) {}

    /**
     * @param  iterable<array{estado: string, prioridad: string, cantidad: int}>  $grupos  conteo por estado y prioridad
     * @param  array<string, int>  $vencidos  vencidos por estado; un estado ausente cuenta 0
     */
    public static function desde(iterable $grupos, array $vencidos): self
    {
        $porEstado = array_fill_keys(array_column(Estado::cases(), 'value'), 0);
        $porPrioridad = array_fill_keys(array_column(Prioridad::cases(), 'value'), 0);

        foreach ($grupos as $grupo) {
            $porEstado[$grupo['estado']] += $grupo['cantidad'];
            $porPrioridad[$grupo['prioridad']] += $grupo['cantidad'];
        }

        $vencidosPorEstado = array_fill_keys(array_column(Vencimiento::estadosQueVencen(), 'value'), 0);
        foreach ($vencidos as $estado => $cantidad) {
            $vencidosPorEstado[$estado] += $cantidad;
        }

        return new self(array_sum($porEstado), $porEstado, $porPrioridad, array_sum($vencidosPorEstado), $vencidosPorEstado);
    }
}
