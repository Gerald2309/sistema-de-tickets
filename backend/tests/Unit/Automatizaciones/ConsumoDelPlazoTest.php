<?php

namespace Tests\Unit\Automatizaciones;

use App\Automatizaciones\Dominio\ConsumoDelPlazo;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

class ConsumoDelPlazoTest extends TestCase
{
    private const CREADO = '2026-03-10 08:00:00';

    // Un plazo de 100 minutos: cada minuto es un 1 %.
    private const LIMITE = '2026-03-10 09:40:00';

    private static function en(string $momento): DateTimeImmutable
    {
        return new DateTimeImmutable($momento);
    }

    public function test_cuenta_hasta_ahora_mientras_sigue_pendiente(): void
    {
        $this->assertSame(80, ConsumoDelPlazo::porcentaje(self::en(self::CREADO), self::en(self::LIMITE), null, self::en('2026-03-10 09:20:00')));
    }

    public function test_pasa_de_cien_cuando_se_vencio(): void
    {
        $this->assertSame(150, ConsumoDelPlazo::porcentaje(self::en(self::CREADO), self::en(self::LIMITE), null, self::en('2026-03-10 10:30:00')));
    }

    public function test_se_detiene_cuando_se_cumplio(): void
    {
        $cumplido = self::en('2026-03-10 08:30:00');

        $this->assertSame(30, ConsumoDelPlazo::porcentaje(self::en(self::CREADO), self::en(self::LIMITE), $cumplido, self::en('2026-03-12 08:00:00')));
    }

    public function test_sin_plazo_no_hay_porcentaje(): void
    {
        $this->assertNull(ConsumoDelPlazo::porcentaje(self::en(self::CREADO), null, null, self::en(self::LIMITE)));
        $this->assertNull(ConsumoDelPlazo::porcentaje(self::en(self::CREADO), self::en(self::CREADO), null, self::en(self::LIMITE)));
    }
}
