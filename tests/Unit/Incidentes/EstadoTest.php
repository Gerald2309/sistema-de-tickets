<?php

namespace Tests\Unit\Incidentes;

use App\Incidentes\Dominio\Estado;
use App\Incidentes\Dominio\Excepciones\TransicionInvalida;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class EstadoTest extends TestCase
{
    /**
     * @return array<string, array{Estado, Estado}>
     */
    public static function transicionesPermitidas(): array
    {
        return [
            'abierto a en progreso' => [Estado::Abierto, Estado::EnProgreso],
            'en progreso a resuelto' => [Estado::EnProgreso, Estado::Resuelto],
            'resuelto a cerrado' => [Estado::Resuelto, Estado::Cerrado],
            'reapertura: resuelto a en progreso' => [Estado::Resuelto, Estado::EnProgreso],
        ];
    }

    /**
     * @return array<string, array{Estado, Estado}>
     */
    public static function transicionesInvalidas(): array
    {
        return [
            'abierto a resuelto (se salta un paso)' => [Estado::Abierto, Estado::Resuelto],
            'abierto a cerrado' => [Estado::Abierto, Estado::Cerrado],
            'en progreso a abierto (no se devuelve)' => [Estado::EnProgreso, Estado::Abierto],
            'en progreso a cerrado' => [Estado::EnProgreso, Estado::Cerrado],
            'resuelto a abierto' => [Estado::Resuelto, Estado::Abierto],
            'cerrado a abierto' => [Estado::Cerrado, Estado::Abierto],
            'cerrado a en progreso' => [Estado::Cerrado, Estado::EnProgreso],
            'cerrado a resuelto' => [Estado::Cerrado, Estado::Resuelto],
            'mismo estado no es una transición' => [Estado::Abierto, Estado::Abierto],
        ];
    }

    #[DataProvider('transicionesPermitidas')]
    public function test_permite_las_transiciones_del_ciclo_de_vida(Estado $desde, Estado $hacia): void
    {
        $this->assertTrue($desde->puedePasarA($hacia));
        $this->assertSame($hacia, $desde->pasarA($hacia));
    }

    #[DataProvider('transicionesInvalidas')]
    public function test_rechaza_las_demas_transiciones(Estado $desde, Estado $hacia): void
    {
        $this->assertFalse($desde->puedePasarA($hacia));

        $this->expectException(TransicionInvalida::class);
        $desde->pasarA($hacia);
    }

    public function test_cerrado_es_un_estado_final(): void
    {
        $this->assertSame([], Estado::Cerrado->siguientes());
    }

    public function test_el_mensaje_de_error_indica_los_estados_permitidos(): void
    {
        try {
            Estado::Abierto->pasarA(Estado::Cerrado);
            $this->fail('Debió lanzar TransicionInvalida.');
        } catch (TransicionInvalida $e) {
            $this->assertStringContainsString('en_progreso', $e->getMessage());
            $this->assertSame(Estado::Abierto, $e->desde);
            $this->assertSame(Estado::Cerrado, $e->hacia);
        }
    }

    public function test_el_mensaje_de_un_estado_final_lo_indica(): void
    {
        $this->expectExceptionMessageMatches('/estado final/');

        Estado::Cerrado->pasarA(Estado::Abierto);
    }
}
