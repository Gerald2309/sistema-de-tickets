<?php

namespace Tests\Unit\Dominio;

use App\Dominio\CodigoIncidente;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class CodigoIncidenteTest extends TestCase
{
    /**
     * @return array<string, array{int, string}>
     */
    public static function codigos(): array
    {
        return [
            'un dígito' => [1, 'INC-000001'],
            'dos dígitos' => [42, 'INC-000042'],
            'seis dígitos exactos' => [999999, 'INC-999999'],
            'más de seis dígitos no se recorta' => [1000000, 'INC-1000000'],
        ];
    }

    /**
     * @return array<string, array{string, int}>
     */
    public static function codigosValidos(): array
    {
        return [
            'con ceros a la izquierda' => ['INC-000042', 42],
            'sin ceros' => ['INC-42', 42],
            'en minúsculas' => ['inc-000042', 42],
            'con espacios alrededor' => ['  INC-000007 ', 7],
            'más de seis dígitos' => ['INC-1000000', 1000000],
        ];
    }

    /**
     * @return array<string, array{string}>
     */
    public static function textosInvalidos(): array
    {
        return [
            'vacío' => [''],
            'solo el número' => ['42'],
            'otro prefijo' => ['TCK-000042'],
            'sin guion' => ['INC000042'],
            'id cero' => ['INC-000000'],
            'con letras' => ['INC-00A042'],
            'negativo' => ['INC--42'],
            'demasiado largo para un entero' => ['INC-9999999999999999999'],
            'texto cualquiera' => ['la impresora no imprime'],
        ];
    }

    #[DataProvider('codigos')]
    public function test_construye_el_codigo_a_partir_del_id(int $id, string $esperado): void
    {
        $this->assertSame($esperado, CodigoIncidente::deId($id));
    }

    #[DataProvider('codigosValidos')]
    public function test_recupera_el_id_de_un_codigo_valido(string $codigo, int $esperado): void
    {
        $this->assertSame($esperado, CodigoIncidente::aId($codigo));
    }

    #[DataProvider('textosInvalidos')]
    public function test_un_texto_que_no_es_un_codigo_no_devuelve_id(string $texto): void
    {
        $this->assertNull(CodigoIncidente::aId($texto));
    }

    public function test_el_codigo_y_el_id_son_equivalentes_en_ambos_sentidos(): void
    {
        foreach ([1, 42, 999999, 1000000, 123456789] as $id) {
            $this->assertSame($id, CodigoIncidente::aId(CodigoIncidente::deId($id)));
        }
    }
}
