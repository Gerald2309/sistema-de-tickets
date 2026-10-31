<?php

namespace Tests\Unit\Incidentes;

use App\Identidad\Dominio\ClavePermiso;
use App\Identidad\Dominio\ConjuntoPermisos;
use App\Incidentes\Dominio\AlcanceIncidentes;
use PHPUnit\Framework\TestCase;

class AlcanceIncidentesTest extends TestCase
{
    private const YO = 10;

    private const AJENO = 99;

    private function alcance(ClavePermiso ...$permisos): AlcanceIncidentes
    {
        return new AlcanceIncidentes(new ConjuntoPermisos($permisos), self::YO);
    }

    private function solicitante(): AlcanceIncidentes
    {
        return $this->alcance(ClavePermiso::CrearIncidente, ClavePermiso::VerPropios, ClavePermiso::EditarPropios, ClavePermiso::VerHistorial);
    }

    private function soporte(): AlcanceIncidentes
    {
        return $this->alcance(
            ClavePermiso::CrearIncidente,
            ClavePermiso::VerTodos,
            ClavePermiso::EditarTodos,
            ClavePermiso::CambiarEstado,
            ClavePermiso::Reasignar,
            ClavePermiso::VerHistorial,
        );
    }

    public function test_quien_solo_ve_lo_propio_no_ve_lo_ajeno(): void
    {
        $this->assertTrue($this->solicitante()->puedeVer(self::YO));
        $this->assertFalse($this->solicitante()->puedeVer(self::AJENO));
        $this->assertFalse($this->solicitante()->veTodo());
    }

    public function test_quien_ve_todo_ve_lo_propio_y_lo_ajeno(): void
    {
        $this->assertTrue($this->soporte()->veTodo());
        $this->assertTrue($this->soporte()->puedeVer(self::YO));
        $this->assertTrue($this->soporte()->puedeVer(self::AJENO));
    }

    public function test_ve_solo_lo_propio_si_tiene_ver_propios_y_no_ver_todos(): void
    {
        $this->assertTrue($this->solicitante()->veSoloLoPropio());
        $this->assertFalse($this->soporte()->veSoloLoPropio());
        $this->assertFalse($this->alcance()->veSoloLoPropio());
        $this->assertFalse($this->alcance(ClavePermiso::CrearIncidente)->veSoloLoPropio());
        $this->assertSame(self::YO, $this->solicitante()->usuarioId());
    }

    public function test_sin_permisos_no_ve_ni_lista_ni_crea(): void
    {
        $nada = $this->alcance();

        $this->assertFalse($nada->puedeVer(self::YO));
        $this->assertFalse($nada->puedeListar());
        $this->assertFalse($nada->puedeCrear());
    }

    public function test_el_solicitante_edita_solo_lo_propio(): void
    {
        $this->assertTrue($this->solicitante()->puedeEditar(self::YO));
        $this->assertFalse($this->solicitante()->puedeEditar(self::AJENO));
    }

    public function test_el_solicitante_no_cambia_estado_ni_reasigna_ni_borra(): void
    {
        $this->assertFalse($this->solicitante()->puedeCambiarEstado(self::YO));
        $this->assertFalse($this->solicitante()->puedeReasignar(self::YO));
        $this->assertFalse($this->solicitante()->puedeBorrar(self::YO));
    }

    public function test_soporte_edita_cambia_estado_y_reasigna_cualquier_incidente_pero_no_borra(): void
    {
        $this->assertTrue($this->soporte()->puedeEditar(self::AJENO));
        $this->assertTrue($this->soporte()->puedeCambiarEstado(self::AJENO));
        $this->assertTrue($this->soporte()->puedeReasignar(self::AJENO));
        $this->assertFalse($this->soporte()->puedeBorrar(self::AJENO));
    }

    public function test_un_permiso_de_accion_no_sirve_sobre_lo_que_no_se_puede_ver(): void
    {
        // Borrar sin ver: no actúa sobre nada. Con ver_propios, solo sobre lo propio.
        $this->assertFalse($this->alcance(ClavePermiso::Borrar)->puedeBorrar(self::YO));
        $this->assertTrue($this->alcance(ClavePermiso::Borrar, ClavePermiso::VerPropios)->puedeBorrar(self::YO));
        $this->assertFalse($this->alcance(ClavePermiso::Borrar, ClavePermiso::VerPropios)->puedeBorrar(self::AJENO));
        $this->assertFalse($this->alcance(ClavePermiso::VerHistorial)->puedeVerHistorial(self::YO));
    }

    public function test_ver_el_historial_exige_su_permiso(): void
    {
        $this->assertTrue($this->solicitante()->puedeVerHistorial(self::YO));
        $this->assertFalse($this->alcance(ClavePermiso::VerPropios)->puedeVerHistorial(self::YO));
    }

    public function test_puede_actualizar_si_puede_hacer_alguna_parte(): void
    {
        $this->assertTrue($this->solicitante()->puedeActualizar(self::YO));
        $this->assertFalse($this->solicitante()->puedeActualizar(self::AJENO));
        $this->assertTrue($this->alcance(ClavePermiso::VerTodos, ClavePermiso::Reasignar)->puedeActualizar(self::AJENO));
        $this->assertFalse($this->alcance(ClavePermiso::VerTodos)->puedeActualizar(self::AJENO));
    }

    public function test_cada_campo_exige_su_capacidad(): void
    {
        $solicitante = $this->solicitante();
        $soporte = $this->soporte();

        $this->assertTrue($solicitante->puedeModificar(['titulo', 'descripcion', 'prioridad', 'categoria', 'fecha_vencimiento'], self::YO));
        $this->assertFalse($solicitante->puedeModificar(['estado'], self::YO));
        $this->assertFalse($solicitante->puedeModificar(['solucion'], self::YO));
        $this->assertFalse($solicitante->puedeModificar(['usuario_asignado_id'], self::YO));
        $this->assertFalse($solicitante->puedeModificar(['titulo', 'estado'], self::YO), 'Un solo campo sin permiso rechaza la petición.');

        $this->assertTrue($soporte->puedeModificar(['estado', 'solucion', 'usuario_asignado_id', 'titulo'], self::AJENO));
    }

    public function test_los_campos_desconocidos_no_se_evaluan(): void
    {
        $this->assertTrue($this->solicitante()->puedeModificar(['usuario_creador_id', 'id'], self::YO));
    }
}
