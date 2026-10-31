<?php

namespace Tests\Unit\Dominio;

use PHPUnit\Framework\TestCase;

// Vigila que el dominio siga siendo PHP puro: sin Laravel ni modelos de la aplicación.
class DominioIndependienteTest extends TestCase
{
    public function test_el_dominio_no_depende_de_laravel_ni_de_los_modelos(): void
    {
        $archivos = glob(dirname(__DIR__, 3).'/app/Dominio/{,*/}*.php', GLOB_BRACE);

        $this->assertNotEmpty($archivos);

        foreach ($archivos as $archivo) {
            $this->assertDoesNotMatchRegularExpression(
                '/^use (Illuminate|App\\\\Models)\b/m',
                file_get_contents($archivo),
                basename($archivo).' depende de Laravel o de los modelos.',
            );
        }
    }
}
