<?php

namespace Tests\Unit\Identidad;

use App\Identidad\Dominio\Administracion;
use App\Identidad\Dominio\Excepciones\UltimoAdministrador;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class AdministracionTest extends TestCase
{
    public function test_basta_con_uno_de_cada_tipo(): void
    {
        Administracion::exigirQueQuede(1, 1);

        $this->addToAssertionCount(1);
    }

    /**
     * @return array<string, array{int, int}>
     */
    public static function conteosInsuficientes(): array
    {
        return [
            'nadie administra roles' => [0, 3],
            'nadie administra usuarios' => [2, 0],
            'nadie administra nada' => [0, 0],
        ];
    }

    #[DataProvider('conteosInsuficientes')]
    public function test_falla_si_alguno_de_los_dos_queda_en_cero(int $roles, int $usuarios): void
    {
        $this->expectException(UltimoAdministrador::class);

        Administracion::exigirQueQuede($roles, $usuarios);
    }
}
