<?php

namespace Tests\Unit\Automatizaciones;

use App\Automatizaciones\Dominio\Hechos;
use App\Incidentes\Dominio\Categoria;
use App\Incidentes\Dominio\Prioridad;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class HechosTest extends TestCase
{
    /**
     * @return array<string, array{string, string, bool}>
     */
    public static function menciones(): array
    {
        return [
            'la misma palabra' => ['La VPN falla', 'VPN', true],
            'sin importar mayúsculas' => ['la vpn falla', 'VPN', true],
            'sin importar tildes' => ['Sin conexión', 'conexion', true],
            'una frase' => ['Hoy no   tengo internet', 'no tengo internet', true],
            'al final, con signos' => ['¿Alguien revisa la VPN?', 'vpn', true],
            'no dentro de otra palabra' => ['Error en vpnx', 'vpn', false],
            'no como parte de otra' => ['Aprobaron el crédito', 'red', false],
            'los signos se buscan tal cual' => ['Costo 3.5', '3.5', true],
            'no está' => ['No llega el correo', 'vpn', false],
        ];
    }

    #[DataProvider('menciones')]
    public function test_menciona_palabras_completas(string $texto, string $buscadas, bool $esperado): void
    {
        $hechos = new Hechos(null, Categoria::Otros, Prioridad::Media, $texto);

        $this->assertSame($esperado, $hechos->menciona($buscadas));
    }
}
