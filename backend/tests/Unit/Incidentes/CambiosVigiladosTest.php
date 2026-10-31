<?php

namespace Tests\Unit\Incidentes;

use App\Incidentes\Dominio\CambiosVigilados;
use App\Incidentes\Dominio\CampoHistorial;
use App\Incidentes\Dominio\EntradaHistorial;
use App\Incidentes\Dominio\EventoHistorial;
use PHPUnit\Framework\TestCase;

class CambiosVigiladosTest extends TestCase
{
    public function test_sin_diferencias_no_hay_entradas(): void
    {
        $valores = ['titulo' => 'VPN', 'estado' => 'abierto'];

        $this->assertSame([], CambiosVigilados::modificaciones($valores, $valores));
    }

    public function test_registra_cada_campo_que_cambio_con_su_valor_anterior_y_nuevo(): void
    {
        $entradas = CambiosVigilados::modificaciones(
            ['titulo' => 'VPN', 'estado' => 'abierto', 'prioridad' => 'media'],
            ['titulo' => 'VPN caída', 'estado' => 'en_progreso', 'prioridad' => 'media'],
        );

        $this->assertCount(2, $entradas);
        $this->assertSame(CampoHistorial::Titulo, $entradas[0]->campo);
        $this->assertSame('VPN', $entradas[0]->valorAnterior);
        $this->assertSame('VPN caída', $entradas[0]->valorNuevo);
        $this->assertSame(CampoHistorial::Estado, $entradas[1]->campo);
        $this->assertSame('en_progreso', $entradas[1]->valorNuevo);
    }

    public function test_las_entradas_siguen_el_orden_de_los_campos_vigilados(): void
    {
        $entradas = CambiosVigilados::modificaciones(
            ['estado' => 'abierto', 'titulo' => 'a'],
            ['estado' => 'cerrado', 'titulo' => 'b'],
        );

        $this->assertSame([CampoHistorial::Titulo, CampoHistorial::Estado], array_map(fn ($e) => $e->campo, $entradas));
    }

    public function test_quitar_o_poner_el_asignado_se_registra_con_valor_nulo(): void
    {
        $asignado = CambiosVigilados::modificaciones(['usuario_asignado' => null], ['usuario_asignado' => '7']);
        $retirado = CambiosVigilados::modificaciones(['usuario_asignado' => '7'], ['usuario_asignado' => null]);

        $this->assertNull($asignado[0]->valorAnterior);
        $this->assertSame('7', $asignado[0]->valorNuevo);
        $this->assertSame('7', $retirado[0]->valorAnterior);
        $this->assertNull($retirado[0]->valorNuevo);
    }

    public function test_un_campo_que_no_esta_en_ambos_lados_o_no_es_vigilado_se_ignora(): void
    {
        $entradas = CambiosVigilados::modificaciones(
            ['titulo' => 'a', 'creado_por' => '1'],
            ['titulo' => 'a', 'creado_por' => '2', 'estado' => 'cerrado'],
        );

        $this->assertSame([], $entradas);
    }

    public function test_creacion_y_borrado_no_llevan_campo_ni_valores(): void
    {
        foreach ([EntradaHistorial::creacion(), EntradaHistorial::borrado()] as $entrada) {
            $this->assertNull($entrada->campo);
            $this->assertNull($entrada->valorAnterior);
            $this->assertNull($entrada->valorNuevo);
        }

        $this->assertSame(EventoHistorial::Creacion, EntradaHistorial::creacion()->evento);
        $this->assertSame(EventoHistorial::Borrado, EntradaHistorial::borrado()->evento);
    }

    public function test_una_modificacion_siempre_lleva_su_campo(): void
    {
        $entrada = EntradaHistorial::modificacion(CampoHistorial::Prioridad, 'media', 'alta');

        $this->assertSame(EventoHistorial::Modificacion, $entrada->evento);
        $this->assertSame(CampoHistorial::Prioridad, $entrada->campo);
    }
}
