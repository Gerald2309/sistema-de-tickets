<?php

namespace Tests\Unit\Incidentes;

use App\Incidentes\Dominio\TerminosDeBusqueda;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class TerminosDeBusquedaTest extends TestCase
{
    /**
     * @return array<string, array{string, list<string>}>
     */
    public static function textos(): array
    {
        return [
            'una palabra' => ['VPN', ['vpn']],
            'varias palabras, en minúsculas' => ['No Puedo Conectarme', ['puedo', 'conectarme']],
            'sin las de menos de 3 letras' => ['no puedo conectarme a la vpn', ['puedo', 'conectarme', 'vpn']],
            'sin repetir' => ['correo CORREO correo', ['correo']],
            'conserva acentos y eñes' => ['conexión contraseña', ['conexión', 'contraseña']],
            'conserva números' => ['error 691 vpn2', ['error', '691', 'vpn2']],
            'espacios y signos de más' => ["  vpn,   casa;\tred.  ", ['vpn', 'casa', 'red']],
            'solo palabras cortas' => ['a la de un', []],
            'texto vacío' => ['', []],
            'solo signos' => ['+-*"()~@<>', []],
        ];
    }

    #[DataProvider('textos')]
    public function test_extrae_las_palabras_utiles(string $texto, array $esperado): void
    {
        $this->assertSame($esperado, TerminosDeBusqueda::desde($texto, 3, 5));
    }

    public function test_los_operadores_del_motor_nunca_llegan_a_la_busqueda(): void
    {
        $terminos = TerminosDeBusqueda::desde('+vpn -casa "red" (nube) ~lenta *todo @4 <a> >b', 3, 10);

        $this->assertSame(['vpn', 'casa', 'red', 'nube', 'lenta', 'todo'], $terminos);

        foreach ($terminos as $termino) {
            $this->assertDoesNotMatchRegularExpression('/[+\-<>()~*"@]/', $termino);
        }
    }

    public function test_conserva_como_maximo_las_primeras_palabras_que_se_piden(): void
    {
        $this->assertSame(
            ['alfa', 'bravo', 'charlie'],
            TerminosDeBusqueda::desde('alfa bravo charlie delta echo foxtrot', 3, 3),
        );
    }

    public function test_el_maximo_cuenta_palabras_utiles_y_no_palabras_descartadas(): void
    {
        $this->assertSame(
            ['uno', 'dos'],
            TerminosDeBusqueda::desde('a b uno c d dos e f tres', 3, 2),
        );
    }

    public function test_respeta_la_longitud_minima_indicada(): void
    {
        $this->assertSame(['vpn', 'casa'], TerminosDeBusqueda::desde('vpn de casa', 3, 5));
        $this->assertSame(['casa'], TerminosDeBusqueda::desde('vpn de casa', 4, 5));
    }

    public function test_un_texto_con_bytes_invalidos_no_rompe_la_busqueda(): void
    {
        $this->assertSame([], TerminosDeBusqueda::desde("\xB1\x31", 3, 5));
    }
}
