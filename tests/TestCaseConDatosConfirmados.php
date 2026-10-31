<?php

namespace Tests;

use Illuminate\Foundation\Testing\DatabaseTruncation;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Tests\Concerns\AyudasDePrueba;

// Para lo que solo funciona con datos confirmados: el índice de texto de MySQL no ve las filas de una
// transacción abierta. Aquí la base se vacía al terminar cada prueba en lugar de revertir una transacción
// (más lento: úsese solo cuando haga falta).
abstract class TestCaseConDatosConfirmados extends BaseTestCase
{
    use AyudasDePrueba, DatabaseTruncation;

    protected function setUp(): void
    {
        parent::setUp();

        $this->prepararDatosBase();
    }

    // La base se vacía antes de cada prueba (lo hace el trait), y también al terminar para no dejar
    // filas confirmadas que vean las pruebas que corren dentro de una transacción.
    protected function tearDown(): void
    {
        $this->truncateDatabaseTables();

        parent::tearDown();
    }
}
