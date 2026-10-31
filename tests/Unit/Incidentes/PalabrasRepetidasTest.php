<?php

namespace Tests\Unit\Incidentes;

use App\Incidentes\Dominio\PalabrasRepetidas;
use PHPUnit\Framework\TestCase;

class PalabrasRepetidasTest extends TestCase
{
    public function test_ordena_por_cuantos_textos_la_contienen_y_desempata_alfabeticamente(): void
    {
        $textos = [
            'Sin internet en el piso',
            'No hay internet ni wifi',
            'Wifi lento y sin internet',
            'Wifi caído',
        ];

        $this->assertSame(['internet', 'wifi'], PalabrasRepetidas::desde($textos, 3, 5));
    }

    public function test_una_palabra_repetida_dentro_de_un_texto_cuenta_una_sola_vez(): void
    {
        $this->assertSame([], PalabrasRepetidas::desde(['cable cable cable cable', 'monitor'], 3, 5));
    }

    public function test_descarta_palabras_comunes_y_cortas_y_respeta_el_maximo(): void
    {
        $textos = ['Esta impresora no imprime', 'Esta impresora tiene el papel atascado', 'impresora papel'];

        $this->assertSame(['impresora'], PalabrasRepetidas::desde($textos, 3, 1));
        $this->assertSame(['impresora', 'papel'], PalabrasRepetidas::desde($textos, 3, 5));
    }

    public function test_los_numeros_se_devuelven_como_texto(): void
    {
        $palabras = PalabrasRepetidas::desde(['error 691 vpn', 'vpn error 691'], 3, 5);

        $this->assertSame(['691', 'error', 'vpn'], $palabras);
    }
}
