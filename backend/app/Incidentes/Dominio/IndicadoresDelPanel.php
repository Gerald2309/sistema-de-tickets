<?php

namespace App\Incidentes\Dominio;

// Indicadores del panel que miran hacia atrás: cuánto lleva esperando lo pendiente, cómo entran y salen los
// incidentes día a día y cuánto se tarda en resolverlos. Los tiempos van en segundos; null cuando no hay datos
// con qué medirlos (mejor «sin datos» que un 0 que parece un resultado).
final readonly class IndicadoresDelPanel
{
    // Límites en días de los tramos de espera: menos de 1, de 1 a 3, de 3 a 7, de 7 a 28 y más de 28.
    public const TRAMOS_DE_ESPERA = [1, 3, 7, 28];

    public const DIAS_DE_TENDENCIA = 14;

    public const DIAS_DE_RESOLUCION = 30;

    /**
     * @param  list<int>  $espera  pendientes por tramo, del más reciente al más antiguo
     * @param  list<array{fecha: string, entraron: int, resueltos: int}>  $tendencia  un día por entrada, del más antiguo a hoy
     * @param  array<string, array{mediana: int|null, plazo: int|null, a_tiempo: float|null, cantidad: int}>  $porPrioridad  una entrada por prioridad
     */
    public function __construct(
        public array $espera,
        public array $tendencia,
        public ?int $medianaDeResolucion,
        public ?int $medianaAnterior,
        public ?int $medianaDelPlazo,
        public ?float $aTiempo,
        public array $porPrioridad,
        public ?int $medianaPrimeraRespuesta,
        public ?float $respuestaATiempo,
        public ?float $devueltos,
    ) {}

    /**
     * El valor del medio (o el promedio de los dos del medio): un caso extremo no la arrastra como al promedio.
     *
     * @param  list<int>  $valores
     */
    public static function mediana(array $valores): ?int
    {
        if ($valores === []) {
            return null;
        }

        sort($valores);
        $mitad = intdiv(count($valores), 2);

        return count($valores) % 2 === 1
            ? $valores[$mitad]
            : intdiv($valores[$mitad - 1] + $valores[$mitad], 2);
    }

    // Parte de un total, entre 0 y 1; sin total no hay proporción.
    public static function proporcion(int $parte, int $total): ?float
    {
        return $total === 0 ? null : round($parte / $total, 3);
    }
}
