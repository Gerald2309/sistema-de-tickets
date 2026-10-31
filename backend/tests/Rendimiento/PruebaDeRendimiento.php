<?php

namespace Tests\Rendimiento;

use App\Identidad\Models\User;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\AyudasDePrueba;

// Base de las pruebas de rendimiento. No usan RefreshDatabase: leen una base ya sembrada con VolumenSeeder
// y no la modifican (solo crean y borran su propio token). Se lanzan aparte de la suite normal:
//
//   vendor/bin/phpunit tests/Rendimiento
//
// La base es incidentes_volumen (o la de BASE_DE_RENDIMIENTO): nunca la de la aplicación ni la de pruebas.
//
// Miden el tiempo de la aplicación completa (middleware, Eloquent, serialización) con una sola conexión y
// sin red ni concurrencia, con datos sintéticos.
abstract class PruebaDeRendimiento extends BaseTestCase
{
    use AyudasDePrueba;

    protected const PETICIONES = 50;

    protected const CALENTAMIENTO = 5;

    // Meta de RNF-06: percentil 95 por debajo de este valor, en milisegundos.
    protected const META_MS = 500;

    /** @var list<array{string, float, float, float}> */
    private static array $resultados = [];

    protected User $administrador;

    protected string $tokenAdministrador;

    protected function setUp(): void
    {
        parent::setUp();

        config(['database.connections.mysql.database' => (string) (getenv('BASE_DE_RENDIMIENTO') ?: 'incidentes_volumen')]);
        DB::purge('mysql');

        if (DB::table('incidentes')->count() < 10_000) {
            $this->markTestSkipped('La base no tiene datos de volumen: ejecutar VolumenSeeder primero.');
        }

        // El límite de peticiones por minuto se prueba aparte: aquí taparía la medición.
        config(['seguridad.limites.api' => 1_000_000]);

        $this->administrador = User::where('email', config('seguridad.administrador.email'))->firstOrFail();
        $this->tokenAdministrador = $this->administrador->createToken('rendimiento')->plainTextToken;
    }

    protected function tearDown(): void
    {
        DB::table('personal_access_tokens')->where('name', 'rendimiento')->delete();

        parent::tearDown();
    }

    public static function tearDownAfterClass(): void
    {
        foreach (self::$resultados as [$nombre, $mediana, $p95, $maximo]) {
            fwrite(STDOUT, sprintf("  %-62s mediana %7.1f   p95 %7.1f   máx %7.1f ms\n", $nombre, $mediana, $p95, $maximo));
        }
        self::$resultados = [];

        parent::tearDownAfterClass();
    }

    /**
     * @param  callable(): mixed  $operacion
     * @return float percentil 95 en milisegundos
     */
    protected function medir(string $nombre, callable $operacion, bool $conMeta = false): float
    {
        for ($i = 0; $i < self::CALENTAMIENTO; $i++) {
            $operacion();
        }

        $tiempos = [];
        for ($i = 0; $i < self::PETICIONES; $i++) {
            $inicio = hrtime(true);
            $operacion();
            $tiempos[] = (hrtime(true) - $inicio) / 1e6;
        }
        sort($tiempos);

        $p95 = $tiempos[(int) ceil(0.95 * count($tiempos)) - 1];
        self::$resultados[] = [($conMeta ? '[meta] ' : '[info] ').$nombre, $tiempos[intdiv(count($tiempos), 2)], $p95, end($tiempos)];

        if ($conMeta) {
            $this->assertLessThan(self::META_MS, $p95, "{$nombre}: p95 de {$p95} ms.");
        }

        return $p95;
    }

    /**
     * Mide una petición autenticada que debe responder 200.
     */
    protected function medirPeticion(string $nombre, string $token, string $ruta, bool $conMeta = false): float
    {
        return $this->medir($nombre, fn () => $this->conToken($token)->getJson($ruta)->assertOk(), $conMeta);
    }
}
