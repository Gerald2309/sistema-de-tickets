<?php

namespace Tests\Unit\Incidentes;

use App\Incidentes\Dominio\CampoFecha;
use App\Incidentes\Dominio\CampoOrden;
use App\Incidentes\Dominio\FiltrosDeIncidentes;
use PHPUnit\Framework\TestCase;

class CamposDeListadoTest extends TestCase
{
    public function test_los_campos_de_orden_apuntan_a_su_columna(): void
    {
        $this->assertSame('id', CampoOrden::Id->columna());
        $this->assertSame('prioridad', CampoOrden::Prioridad->columna());
        $this->assertSame('estado', CampoOrden::Estado->columna());
        $this->assertSame('fecha_vencimiento', CampoOrden::FechaVencimiento->columna());
        $this->assertSame('created_at', CampoOrden::Creado->columna());
    }

    public function test_el_titulo_no_es_un_campo_de_orden(): void
    {
        $this->assertNull(CampoOrden::tryFrom('titulo'));
        $this->assertNull(CampoOrden::tryFrom('descripcion'));
        $this->assertNull(CampoOrden::tryFrom('usuario_creador_id'));
    }

    public function test_la_fecha_del_rango_es_el_vencimiento_o_la_creacion(): void
    {
        $this->assertSame('fecha_vencimiento', CampoFecha::Vencimiento->columna());
        $this->assertSame('created_at', CampoFecha::Creacion->columna());
    }

    public function test_los_filtros_por_defecto_listan_de_15_en_15_por_id_descendente(): void
    {
        $filtros = new FiltrosDeIncidentes;

        $this->assertSame([], $filtros->terminos);
        $this->assertSame(15, $filtros->porPagina);
        $this->assertSame(100, FiltrosDeIncidentes::POR_PAGINA_MAXIMO);
        $this->assertSame(CampoOrden::Id, $filtros->ordenarPor);
        $this->assertSame('desc', $filtros->direccion->value);
        $this->assertSame(CampoFecha::Vencimiento, $filtros->campoFecha);
    }
}
