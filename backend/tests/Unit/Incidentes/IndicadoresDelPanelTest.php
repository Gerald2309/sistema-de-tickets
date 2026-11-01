<?php

namespace Tests\Unit\Incidentes;

use App\Incidentes\Dominio\IndicadoresDelPanel;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class IndicadoresDelPanelTest extends TestCase
{
    /**
     * @return array<string, array{list<int>, int|null}>
     */
    public static function medianas(): array
    {
        return [
            'sin valores' => [[], null],
            'uno' => [[7], 7],
            'impar, desordenado' => [[9, 1, 5], 5],
            'par: promedio de los dos del medio' => [[1, 2, 4, 100], 3],
            'un extremo no la arrastra' => [[2, 3, 4, 5000], 3],
        ];
    }

    /**
     * @param  list<int>  $valores
     */
    #[DataProvider('medianas')]
    public function test_mediana(array $valores, ?int $esperada): void
    {
        $this->assertSame($esperada, IndicadoresDelPanel::mediana($valores));
    }

    public function test_proporcion_sin_total_es_nula_y_se_redondea_a_tres_decimales(): void
    {
        $this->assertNull(IndicadoresDelPanel::proporcion(0, 0));
        $this->assertSame(0.333, IndicadoresDelPanel::proporcion(1, 3));
        $this->assertSame(1.0, IndicadoresDelPanel::proporcion(4, 4));
    }
}
