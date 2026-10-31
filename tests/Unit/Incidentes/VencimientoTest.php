<?php

namespace Tests\Unit\Incidentes;

use App\Incidentes\Dominio\Estado;
use App\Incidentes\Dominio\Vencimiento;
use DateTimeImmutable;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class VencimientoTest extends TestCase
{
    // El instante actual se pasa como argumento: la regla no depende del reloj.
    private const AHORA = '2025-06-15 12:00:00';

    /**
     * @return array<string, array{string, Estado, bool}>
     */
    public static function casos(): array
    {
        return [
            'abierto con vencimiento pasado' => ['2025-06-15 11:59:59', Estado::Abierto, true],
            'en progreso con vencimiento pasado' => ['2025-06-14 08:00:00', Estado::EnProgreso, true],
            'abierto con vencimiento futuro' => ['2025-06-15 12:00:01', Estado::Abierto, false],
            'justo en el instante actual no está vencido' => [self::AHORA, Estado::Abierto, false],
            'resuelto con vencimiento pasado' => ['2025-06-01 08:00:00', Estado::Resuelto, false],
            'cerrado con vencimiento pasado' => ['2025-06-01 08:00:00', Estado::Cerrado, false],
        ];
    }

    #[DataProvider('casos')]
    public function test_define_cuando_un_incidente_esta_vencido(string $vencimiento, Estado $estado, bool $esperado): void
    {
        $this->assertSame(
            $esperado,
            Vencimiento::estaVencido(new DateTimeImmutable($vencimiento), $estado, new DateTimeImmutable(self::AHORA)),
        );
    }

    public function test_solo_vencen_los_estados_sin_resolver(): void
    {
        $this->assertSame([Estado::Abierto, Estado::EnProgreso], Vencimiento::estadosQueVencen());
    }
}
