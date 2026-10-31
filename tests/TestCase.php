<?php

namespace Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Tests\Concerns\AyudasDePrueba;

// Base de las pruebas de funcionalidad: cada prueba corre dentro de una transacción que se revierte.
abstract class TestCase extends BaseTestCase
{
    use AyudasDePrueba, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->prepararDatosBase();
    }
}
