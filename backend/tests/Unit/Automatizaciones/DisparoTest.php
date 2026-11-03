<?php

namespace Tests\Unit\Automatizaciones;

use App\Automatizaciones\Dominio\Disparador;
use App\Automatizaciones\Dominio\Disparo;
use App\Automatizaciones\Dominio\Excepciones\FlujoInvalido;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class DisparoTest extends TestCase
{
    /**
     * @return array<string, array{string, array<string, mixed>, array<string, int|string|null>}>
     */
    public static function validos(): array
    {
        $todo = ['espera_minutos' => 240, 'porcentaje' => 80, 'plazo' => 'respuesta'];

        return [
            'al crearse no lleva medida' => ['creacion', $todo, [null, null, null]],
            'sin respuesta lleva la espera' => ['sin_respuesta', $todo, [240, null, null]],
            'resuelto sin confirmar lleva la espera' => ['resuelto_sin_confirmar', $todo, [240, null, null]],
            'vencido lleva el plazo' => ['sla_vencido', $todo, [null, null, 'respuesta']],
            'por vencer lleva porcentaje y plazo' => ['sla_por_vencer', $todo, [null, 80, 'respuesta']],
        ];
    }

    /**
     * @param  array<string, mixed>  $medida
     * @param  list<int|string|null>  $guardado  espera, porcentaje y plazo
     */
    #[DataProvider('validos')]
    public function test_solo_guarda_la_medida_que_usa_su_disparador(string $disparador, array $medida, array $guardado): void
    {
        $disparo = Disparo::desde($disparador, $medida);

        $this->assertSame($disparador, $disparo->disparador->value);
        $this->assertSame($guardado, array_values(array_slice($disparo->aArreglo(), 1)));
    }

    /**
     * @return array<string, array{mixed, array<string, mixed>, string}>
     */
    public static function invalidos(): array
    {
        return [
            'un disparador que no existe' => ['al_editarse', [], 'disparador'],
            'sin espera' => ['sin_respuesta', [], 'espera_minutos'],
            'espera en cero' => ['sin_respuesta', ['espera_minutos' => 0], 'espera_minutos'],
            'espera de más de 90 días' => ['resuelto_sin_confirmar', ['espera_minutos' => Disparo::ESPERA_MAXIMA_MINUTOS + 1], 'espera_minutos'],
            'espera como texto' => ['sin_respuesta', ['espera_minutos' => '60'], 'espera_minutos'],
            'vencido sin plazo' => ['sla_vencido', [], 'plazo'],
            'un plazo que no existe' => ['sla_vencido', ['plazo' => 'cierre'], 'plazo'],
            'por vencer sin porcentaje' => ['sla_por_vencer', ['plazo' => 'resolucion'], 'porcentaje'],
            'por vencer al 100 %' => ['sla_por_vencer', ['plazo' => 'resolucion', 'porcentaje' => 100], 'porcentaje'],
        ];
    }

    /**
     * @param  array<string, mixed>  $medida
     */
    #[DataProvider('invalidos')]
    public function test_rechaza_una_medida_que_no_sirve_y_dice_cual(mixed $disparador, array $medida, string $ruta): void
    {
        try {
            Disparo::desde($disparador, $medida);
            $this->fail('Debió rechazarla.');
        } catch (FlujoInvalido $error) {
            $this->assertSame($ruta, $error->ruta);
        }
    }

    public function test_los_disparadores_por_tiempo_son_todos_menos_la_creacion(): void
    {
        $this->assertNotContains(Disparador::Creacion, Disparador::porTiempo());
        $this->assertCount(count(Disparador::cases()) - 1, Disparador::porTiempo());
    }
}
