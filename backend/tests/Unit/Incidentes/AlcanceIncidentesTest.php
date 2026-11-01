<?php

namespace Tests\Unit\Incidentes;

use App\Identidad\Dominio\ClavePermiso;
use App\Identidad\Dominio\ConjuntoPermisos;
use App\Incidentes\Dominio\AlcanceIncidentes;
use App\Incidentes\Dominio\Participantes;
use PHPUnit\Framework\TestCase;

class AlcanceIncidentesTest extends TestCase
{
    private const YO = 10;

    private const AJENO = 99;

    private const OTRA_PERSONA = 55;

    private function alcance(ClavePermiso ...$permisos): AlcanceIncidentes
    {
        return new AlcanceIncidentes(new ConjuntoPermisos($permisos), self::YO);
    }

    private function solicitante(): AlcanceIncidentes
    {
        return $this->alcance(ClavePermiso::CrearIncidente, ClavePermiso::VerPropios, ClavePermiso::EditarPropios, ClavePermiso::VerHistorial);
    }

    private function soporte(ClavePermiso ...$extra): AlcanceIncidentes
    {
        return $this->alcance(
            ClavePermiso::CrearIncidente,
            ClavePermiso::VerTodos,
            ClavePermiso::EditarTodos,
            ClavePermiso::CambiarEstado,
            ClavePermiso::VerHistorial,
            ...$extra,
        );
    }

    private function administrador(): AlcanceIncidentes
    {
        return $this->soporte(ClavePermiso::Reasignar, ClavePermiso::Borrar);
    }

    private static function mioLibre(): Participantes
    {
        return new Participantes(self::YO, null);
    }

    private static function mioTomado(): Participantes
    {
        return new Participantes(self::YO, self::OTRA_PERSONA);
    }

    private static function ajenoLibre(): Participantes
    {
        return new Participantes(self::AJENO, null);
    }

    private static function ajenoQueAtiendo(): Participantes
    {
        return new Participantes(self::AJENO, self::YO);
    }

    private static function ajenoQueAtiendeOtro(): Participantes
    {
        return new Participantes(self::AJENO, self::OTRA_PERSONA);
    }

    public function test_quien_solo_ve_lo_propio_no_ve_lo_ajeno(): void
    {
        $this->assertTrue($this->solicitante()->puedeVer(self::mioLibre()));
        $this->assertFalse($this->solicitante()->puedeVer(self::ajenoLibre()));
        $this->assertFalse($this->solicitante()->veTodo());
    }

    public function test_quien_ve_todo_ve_lo_propio_y_lo_ajeno(): void
    {
        $this->assertTrue($this->soporte()->veTodo());
        $this->assertTrue($this->soporte()->puedeVer(self::mioLibre()));
        $this->assertTrue($this->soporte()->puedeVer(self::ajenoQueAtiendeOtro()));
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

        $this->assertFalse($nada->puedeVer(self::mioLibre()));
        $this->assertFalse($nada->puedeListar());
        $this->assertFalse($nada->puedeCrear());
    }

    public function test_el_solicitante_edita_lo_suyo_solo_mientras_nadie_lo_ha_tomado(): void
    {
        $this->assertTrue($this->solicitante()->puedeClasificar(self::mioLibre()));
        $this->assertFalse($this->solicitante()->puedeClasificar(self::mioTomado()));
        $this->assertFalse($this->solicitante()->puedeClasificar(self::ajenoLibre()));
    }

    public function test_solo_quien_reporto_el_incidente_confirma_su_solucion(): void
    {
        $this->assertTrue($this->solicitante()->puedeConfirmarSolucion(self::mioTomado()));
        $this->assertFalse($this->solicitante()->puedeConfirmarSolucion(self::ajenoLibre()));
        $this->assertFalse($this->soporte()->puedeConfirmarSolucion(self::ajenoQueAtiendo()));
        $this->assertFalse($this->administrador()->puedeConfirmarSolucion(self::ajenoQueAtiendo()));
    }

    public function test_el_solicitante_no_toma_ni_cambia_estado_ni_reasigna_ni_borra(): void
    {
        $this->assertFalse($this->solicitante()->puedeTomar(self::mioLibre()));
        $this->assertFalse($this->solicitante()->puedeCambiarEstado(self::mioLibre()));
        $this->assertFalse($this->solicitante()->puedeReasignar(self::mioLibre()));
        $this->assertFalse($this->solicitante()->puedeBorrar(self::mioLibre()));
    }

    public function test_soporte_toma_incidentes_y_solo_trabaja_los_que_atiende(): void
    {
        $this->assertTrue($this->soporte()->puedeTomar(self::ajenoLibre()));

        $this->assertTrue($this->soporte()->puedeClasificar(self::ajenoQueAtiendo()));
        $this->assertTrue($this->soporte()->puedeCambiarEstado(self::ajenoQueAtiendo()));

        foreach ([self::ajenoLibre(), self::ajenoQueAtiendeOtro()] as $noLoAtiende) {
            $this->assertFalse($this->soporte()->puedeClasificar($noLoAtiende));
            $this->assertFalse($this->soporte()->puedeCambiarEstado($noLoAtiende));
            $this->assertFalse($this->soporte()->puedeActualizar($noLoAtiende));
        }

        $this->assertFalse($this->soporte()->puedeBorrar(self::ajenoQueAtiendo()));
    }

    public function test_solo_quien_lo_reporto_corrige_titulo_y_descripcion_y_solo_mientras_nadie_lo_ha_tomado(): void
    {
        foreach (['titulo' => 'Otro título', 'descripcion' => 'Otro texto'] as $campo => $valor) {
            $this->assertTrue($this->solicitante()->puedeModificar([$campo => $valor], self::mioLibre()));
            $this->assertFalse($this->solicitante()->puedeModificar([$campo => $valor], new Participantes(self::YO, self::OTRA_PERSONA)));
            $this->assertFalse($this->soporte()->puedeModificar([$campo => $valor], self::ajenoQueAtiendo()));
            $this->assertFalse($this->administrador()->puedeModificar([$campo => $valor], new Participantes(self::YO, self::YO)));
        }
        $this->assertTrue($this->soporte()->puedeModificar(['prioridad' => 'alta'], self::ajenoQueAtiendo()));
    }

    public function test_quien_lo_atiende_lo_pasa_a_otra_persona_pero_no_lo_deja_sin_asignar(): void
    {
        $this->assertTrue($this->soporte()->puedeReasignar(self::ajenoQueAtiendo()));
        $this->assertFalse($this->soporte()->puedeReasignar(self::ajenoQueAtiendeOtro()));
        $this->assertFalse($this->soporte()->puedeDejarSinAsignar(self::ajenoQueAtiendo()));

        $this->assertTrue($this->soporte()->puedeModificar(['usuario_asignado_id' => self::OTRA_PERSONA], self::ajenoQueAtiendo()));
        $this->assertFalse($this->soporte()->puedeModificar(['usuario_asignado_id' => null], self::ajenoQueAtiendo()));
    }

    public function test_quien_tiene_reasignar_mueve_cualquiera_y_puede_dejarlo_sin_asignar(): void
    {
        $this->assertTrue($this->administrador()->puedeReasignar(self::ajenoQueAtiendeOtro()));
        $this->assertTrue($this->administrador()->puedeModificar(['usuario_asignado_id' => null], self::ajenoQueAtiendeOtro()));
        $this->assertTrue($this->administrador()->puedeAsignarAlCrear());
        $this->assertFalse($this->soporte()->puedeAsignarAlCrear());
    }

    public function test_reasignar_no_permite_trabajar_lo_que_no_se_atiende(): void
    {
        $this->assertFalse($this->administrador()->puedeClasificar(self::ajenoQueAtiendeOtro()));
        $this->assertFalse($this->administrador()->puedeCambiarEstado(self::ajenoQueAtiendeOtro()));
    }

    public function test_conversan_quien_reporto_el_incidente_y_quien_lo_atiende(): void
    {
        $this->assertTrue($this->solicitante()->puedeConversar(self::mioTomado()));
        $this->assertTrue($this->soporte()->puedeConversar(self::ajenoQueAtiendo()));
        $this->assertFalse($this->soporte()->puedeConversar(self::ajenoQueAtiendeOtro()));
        $this->assertFalse($this->administrador()->puedeConversar(self::ajenoLibre()));
        $this->assertFalse($this->solicitante()->puedeConversar(self::ajenoLibre()));
    }

    public function test_las_notas_internas_las_escribe_quien_atiende_y_las_lee_soporte(): void
    {
        $this->assertTrue($this->soporte()->puedeEscribirNotaInterna(self::ajenoQueAtiendo()));
        $this->assertFalse($this->soporte()->puedeEscribirNotaInterna(self::ajenoQueAtiendeOtro()));
        $this->assertFalse($this->solicitante()->puedeEscribirNotaInterna(self::mioTomado()));

        $this->assertTrue($this->soporte()->puedeLeerNotasInternas());
        $this->assertFalse($this->solicitante()->puedeLeerNotasInternas());
    }

    public function test_un_permiso_de_accion_no_sirve_sobre_lo_que_no_se_puede_ver(): void
    {
        // Borrar sin ver: no actúa sobre nada. Con ver_propios, solo sobre lo propio.
        $this->assertFalse($this->alcance(ClavePermiso::Borrar)->puedeBorrar(self::mioLibre()));
        $this->assertTrue($this->alcance(ClavePermiso::Borrar, ClavePermiso::VerPropios)->puedeBorrar(self::mioLibre()));
        $this->assertFalse($this->alcance(ClavePermiso::Borrar, ClavePermiso::VerPropios)->puedeBorrar(self::ajenoLibre()));
        $this->assertFalse($this->alcance(ClavePermiso::VerHistorial)->puedeVerHistorial(self::mioLibre()));
        $this->assertFalse($this->alcance(ClavePermiso::CambiarEstado)->puedeTomar(self::ajenoLibre()));
    }

    public function test_los_casos_parecidos_son_solo_para_quien_ve_todo(): void
    {
        $this->assertTrue($this->soporte()->puedeVerSimilares(self::ajenoLibre()));
        $this->assertFalse($this->solicitante()->puedeVerSimilares(self::mioLibre()));
        $this->assertFalse($this->alcance(ClavePermiso::VerPropios, ClavePermiso::VerHistorial)->puedeVerSimilares(self::mioLibre()));
        $this->assertFalse($this->alcance()->puedeVerSimilares(self::mioLibre()));
    }

    public function test_ver_el_historial_exige_su_permiso(): void
    {
        $this->assertTrue($this->solicitante()->puedeVerHistorial(self::mioLibre()));
        $this->assertFalse($this->alcance(ClavePermiso::VerPropios)->puedeVerHistorial(self::mioLibre()));
    }

    public function test_puede_actualizar_si_puede_hacer_alguna_parte(): void
    {
        $this->assertTrue($this->solicitante()->puedeActualizar(self::mioLibre()));
        $this->assertFalse($this->solicitante()->puedeActualizar(self::ajenoLibre()));
        $this->assertTrue($this->alcance(ClavePermiso::VerTodos, ClavePermiso::Reasignar)->puedeActualizar(self::ajenoLibre()));
        $this->assertFalse($this->alcance(ClavePermiso::VerTodos)->puedeActualizar(self::ajenoLibre()));
    }

    public function test_el_administrador_cambia_la_prioridad_de_cualquiera_pero_no_el_resto_de_la_clasificacion(): void
    {
        $this->assertTrue($this->administrador()->puedeModificar(['prioridad' => 'critica'], self::ajenoQueAtiendeOtro()));
        $this->assertTrue($this->administrador()->puedeModificar(['prioridad' => 'alta'], self::ajenoLibre()));
        $this->assertFalse($this->administrador()->puedeModificar(['categoria' => 'red'], self::ajenoQueAtiendeOtro()));
        $this->assertFalse($this->administrador()->puedeModificar(['fecha_vencimiento' => 'c'], self::ajenoQueAtiendeOtro()));
        $this->assertFalse($this->soporte()->puedeModificar(['prioridad' => 'alta'], self::ajenoQueAtiendeOtro()), 'Soporte solo la cambia en lo que atiende.');
    }

    public function test_cada_campo_exige_su_capacidad(): void
    {
        $contenido = ['prioridad' => 'alta', 'categoria' => 'red'];

        $this->assertTrue($this->solicitante()->puedeModificar($contenido, self::mioLibre()));
        $this->assertFalse($this->solicitante()->puedeModificar(['prioridad' => 'critica'], self::mioLibre()), 'Lo crítico lo decide soporte.');
        $this->assertFalse($this->solicitante()->puedeModificar(['fecha_vencimiento' => 'c'], self::mioLibre()), 'El vencimiento lo decide soporte.');
        $this->assertTrue($this->soporte()->puedeModificar(['prioridad' => 'critica', 'fecha_vencimiento' => 'c'], self::ajenoQueAtiendo()));
        $this->assertFalse($this->solicitante()->puedeModificar(['estado' => 'cerrado'], self::mioLibre()));
        $this->assertFalse($this->solicitante()->puedeModificar(['solucion' => 'x'], self::mioLibre()));
        $this->assertFalse($this->solicitante()->puedeModificar(['usuario_asignado_id' => self::YO], self::mioLibre()));
        $this->assertFalse($this->solicitante()->puedeModificar(['prioridad' => 'alta', 'estado' => 'cerrado'], self::mioLibre()), 'Un solo campo sin permiso rechaza la petición.');

        $this->assertTrue($this->soporte()->puedeModificar(['estado' => 'resuelto', 'solucion' => 'x', 'prioridad' => 'alta'], self::ajenoQueAtiendo()));
    }

    public function test_los_campos_desconocidos_no_se_evaluan(): void
    {
        $this->assertTrue($this->solicitante()->puedeModificar(['usuario_creador_id' => 1, 'id' => 2], self::mioLibre()));
    }
}
