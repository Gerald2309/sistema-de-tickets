<?php

namespace Tests\Unit\Incidentes;

use App\Incidentes\Dominio\CeldaDeHoja;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class CeldaDeHojaTest extends TestCase
{
    /**
     * @return array<string, array{string}>
     */
    public static function inicioDeFormula(): array
    {
        return [
            'igual' => ['=HYPERLINK("http://malo.example","clic")'],
            'más' => ['+1+1'],
            'menos' => ['-2+3'],
            'arroba' => ['@SUM(A1)'],
            'tabulador' => ["\t=1+1"],
            'retorno de carro' => ["\r=1+1"],
        ];
    }

    #[DataProvider('inicioDeFormula')]
    public function test_un_texto_que_empieza_como_formula_se_lee_como_texto(string $texto): void
    {
        $this->assertSame("'".$texto, CeldaDeHoja::segura($texto));
    }

    public function test_el_texto_normal_no_cambia(): void
    {
        $this->assertSame('La impresora no imprime', CeldaDeHoja::segura('La impresora no imprime'));
        $this->assertSame('Error = 49.4C02', CeldaDeHoja::segura('Error = 49.4C02'));
        $this->assertSame('', CeldaDeHoja::segura(''));
    }

    public function test_un_valor_nulo_sale_como_texto_vacio(): void
    {
        $this->assertSame('', CeldaDeHoja::segura(null));
    }
}
