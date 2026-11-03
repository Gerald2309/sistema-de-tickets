<?php

namespace Tests\Unit;

use FilesystemIterator;
use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

// Vigila la estructura modular: el dominio de cada módulo es PHP puro y los módulos
// dependen unos de otros en un solo sentido.
class ArquitecturaTest extends TestCase
{
    // Módulo => módulos de los que no puede depender.
    private const PROHIBIDO = [
        'Auditoria' => ['Identidad', 'Incidentes', 'Automatizaciones'],
        'Identidad' => ['Incidentes', 'Automatizaciones'],
        'Incidentes' => ['Auditoria', 'Automatizaciones'],
        'Automatizaciones' => ['Auditoria'],
    ];

    public function test_el_dominio_de_cada_modulo_no_depende_de_laravel_ni_de_los_modelos(): void
    {
        $archivos = $this->archivos('app/*/Dominio');

        $this->assertNotEmpty($archivos);

        foreach ($archivos as $archivo) {
            $this->assertDoesNotMatchRegularExpression(
                '/^use (Illuminate|App\\\\[A-Za-z]+\\\\Models)\b/m',
                file_get_contents($archivo),
                $this->relativo($archivo).' depende de Laravel o de un modelo.',
            );
        }
    }

    public function test_los_modulos_dependen_unos_de_otros_en_un_solo_sentido(): void
    {
        foreach (self::PROHIBIDO as $modulo => $prohibidos) {
            $archivos = $this->archivos("app/$modulo");

            $this->assertNotEmpty($archivos, "El módulo $modulo no tiene archivos.");

            foreach ($archivos as $archivo) {
                $contenido = file_get_contents($archivo);

                foreach ($prohibidos as $otro) {
                    $this->assertStringNotContainsString(
                        "App\\$otro\\",
                        $contenido,
                        $this->relativo($archivo)." usa el módulo $otro.",
                    );
                }
            }
        }
    }

    // PHP trata cualquier carácter no ASCII como parte del nombre de una variable: en "«$titulo»" la
    // variable es `$titulo»`, que no existe. Se escribe "«{$titulo}»".
    public function test_ninguna_variable_interpolada_va_pegada_a_un_caracter_no_ascii(): void
    {
        foreach (['app', 'config', 'database', 'routes', 'tests'] as $carpeta) {
            foreach ($this->archivos($carpeta) as $archivo) {
                if (str_ends_with($archivo, 'ArquitecturaTest.php')) {
                    continue;
                }

                $this->assertDoesNotMatchRegularExpression(
                    '/"[^"\n]*\$[A-Za-z_][A-Za-z0-9_]*[^\x00-\x7F][^"\n]*"/u',
                    file_get_contents($archivo),
                    $this->relativo($archivo).' interpola una variable pegada a un carácter no ASCII; usa llaves: {$variable}.',
                );
            }
        }
    }

    public function test_no_quedan_clases_de_negocio_fuera_de_los_modulos(): void
    {
        $this->assertDirectoryDoesNotExist($this->raiz().'/app/Dominio');
        $this->assertDirectoryDoesNotExist($this->raiz().'/app/Models');
    }

    /**
     * @return list<string>
     */
    private function archivos(string $patronDeDirectorio): array
    {
        $archivos = [];

        foreach (glob($this->raiz().'/'.$patronDeDirectorio, GLOB_ONLYDIR) as $directorio) {
            $recorrido = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directorio, FilesystemIterator::SKIP_DOTS));

            foreach ($recorrido as $archivo) {
                if ($archivo->getExtension() === 'php') {
                    $archivos[] = $archivo->getPathname();
                }
            }
        }

        return $archivos;
    }

    private function raiz(): string
    {
        return dirname(__DIR__, 2);
    }

    private function relativo(string $archivo): string
    {
        return substr($archivo, strlen($this->raiz()) + 1);
    }
}
