<?php

namespace Tests\Unit\Incidentes;

use App\Incidentes\Dominio\PalabrasVacias;
use App\Incidentes\Dominio\TerminosDeBusqueda;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class PalabrasVaciasTest extends TestCase
{
    /**
     * @return array<string, array{string}>
     */
    public static function palabrasDeUnaMesaDeSoporte(): array
    {
        return [
            'VPN' => ['vpn'],
            'impresora' => ['impresora'],
            'correo' => ['correo'],
            'contraseña' => ['contraseña'],
            'servidor' => ['servidor'],
            'conexión' => ['conexión'],
            'error' => ['error'],
        ];
    }

    public function test_la_lista_esta_en_minusculas_sin_repetir_y_con_palabras_de_tres_o_mas_letras(): void
    {
        $lista = PalabrasVacias::todas();

        $this->assertSame(array_values(array_unique($lista)), $lista, 'Hay palabras repetidas.');

        foreach ($lista as $palabra) {
            $this->assertSame(mb_strtolower($palabra), $palabra, "«{$palabra}» no está en minúsculas.");
            $this->assertGreaterThanOrEqual(3, mb_strlen($palabra), "«{$palabra}» tiene menos de 3 letras.");
        }
    }

    public function test_reconoce_las_palabras_muy_comunes_sin_importar_mayusculas(): void
    {
        foreach (['para', 'PARA', 'Que', 'desde', 'también', 'puedo'] as $palabra) {
            $this->assertTrue(PalabrasVacias::esVacia($palabra), $palabra);
        }
    }

    #[DataProvider('palabrasDeUnaMesaDeSoporte')]
    public function test_no_descarta_el_vocabulario_que_distingue_un_incidente(string $palabra): void
    {
        $this->assertFalse(PalabrasVacias::esVacia($palabra));
    }

    public function test_los_terminos_pueden_excluir_las_palabras_vacias(): void
    {
        $texto = 'No puedo conectarme a la VPN desde casa para trabajar';

        $this->assertSame(['conectarme', 'vpn', 'casa', 'trabajar'], TerminosDeBusqueda::desde($texto, 3, 10, sinPalabrasVacias: true));
        $this->assertContains('para', TerminosDeBusqueda::desde($texto, 3, 10), 'Sin la opción, se conservan.');
    }

    public function test_el_maximo_se_aplica_despues_de_quitar_las_palabras_vacias(): void
    {
        $this->assertSame(
            ['vpn', 'casa'],
            TerminosDeBusqueda::desde('para que desde puedo vpn desde casa', 3, 2, sinPalabrasVacias: true),
        );
    }

    public function test_un_texto_hecho_solo_de_palabras_vacias_no_deja_terminos(): void
    {
        $this->assertSame([], TerminosDeBusqueda::desde('para que desde todo esto', 3, 5, sinPalabrasVacias: true));
    }
}
