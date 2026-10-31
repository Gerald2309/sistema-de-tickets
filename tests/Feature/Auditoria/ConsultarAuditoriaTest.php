<?php

namespace Tests\Feature\Auditoria;

use App\Auditoria\Acciones\RegistrarAuditoria;
use App\Auditoria\Dominio\AccionAuditoria;
use App\Identidad\Dominio\ClavePermiso;
use App\Identidad\Models\User;
use Tests\TestCase;

class ConsultarAuditoriaTest extends TestCase
{
    private User $administrador;

    private string $token;

    protected function setUp(): void
    {
        parent::setUp();

        $this->prepararDatosBase();
        $this->freezeTime();
        $this->administrador = $this->usuarioConPermisos(ClavePermiso::VerAuditoria);
        $this->token = $this->iniciarSesion($this->administrador);
    }

    private function registrar(AccionAuditoria $accion, ?User $actor, ?User $afectado = null, ?string $detalle = null): void
    {
        app(RegistrarAuditoria::class)->ejecutar($accion, $actor?->id, $afectado?->id, $detalle, '192.168.10.5');
    }

    public function test_lista_lo_mas_reciente_primero_con_los_nombres(): void
    {
        $afectado = $this->crearUsuario('Solicitante', ['name' => 'Marta Ruiz']);
        $this->registrar(AccionAuditoria::UsuarioCreado, $this->administrador, $afectado, 'Rol «Solicitante»');
        $this->travel(5)->minutes();
        $this->registrar(AccionAuditoria::LoginFallido, null, $afectado);

        $respuesta = $this->conToken($this->token)->getJson('/api/v1/auditoria')->assertOk()->assertJsonCount(2, 'data');

        $respuesta->assertJsonPath('data.0.accion', 'login_fallido')
            ->assertJsonPath('data.0.usuario', null)
            ->assertJsonPath('data.0.usuario_afectado.name', 'Marta Ruiz')
            ->assertJsonPath('data.1.accion', 'usuario_creado')
            ->assertJsonPath('data.1.usuario.id', $this->administrador->id)
            ->assertJsonPath('data.1.usuario.name', $this->administrador->name)
            ->assertJsonPath('data.1.detalle', 'Rol «Solicitante»')
            ->assertJsonPath('data.1.ip', '192.168.10.5');
    }

    public function test_filtra_por_accion_usuario_y_rango_de_fechas(): void
    {
        $otro = $this->crearUsuario();
        $this->registrar(AccionAuditoria::UsuarioCreado, $this->administrador, $otro);
        $this->travel(1)->hours();
        $this->registrar(AccionAuditoria::RolCreado, $this->administrador);
        $this->registrar(AccionAuditoria::RolCreado, $otro);
        $this->travel(1)->hours();
        $this->registrar(AccionAuditoria::RolEliminado, $this->administrador);

        $consultar = fn (string $consulta) => $this->conToken($this->token)->getJson("/api/v1/auditoria?{$consulta}")->assertOk();

        $consultar('accion=rol_creado')->assertJsonCount(2, 'data');
        $consultar('accion=rol_creado&usuario_id='.$otro->id)->assertJsonCount(1, 'data');
        $consultar('usuario_afectado_id='.$otro->id)->assertJsonCount(1, 'data')->assertJsonPath('data.0.accion', 'usuario_creado');
        $consultar('desde='.urlencode(now()->subMinutes(90)->format('Y-m-d\TH:i:s\Z')))->assertJsonCount(3, 'data');
        $consultar('desde='.urlencode(now()->subMinutes(90)->format('Y-m-d\TH:i:s\Z')).'&hasta='.urlencode(now()->subMinutes(30)->format('Y-m-d\TH:i:s\Z')))->assertJsonCount(2, 'data');
    }

    public function test_pagina_los_resultados(): void
    {
        foreach (range(1, 5) as $i) {
            $this->registrar(AccionAuditoria::RolCreado, $this->administrador);
        }

        $this->conToken($this->token)->getJson('/api/v1/auditoria?per_page=2&page=3')
            ->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('meta.total', 5);
    }

    public function test_rechaza_filtros_invalidos(): void
    {
        $this->conToken($this->token)->getJson('/api/v1/auditoria?accion=volar&usuario_id=abc&per_page=101')
            ->assertUnprocessable()->assertJsonValidationErrors(['accion', 'usuario_id', 'per_page'], 'errors');
        $this->conToken($this->token)->getJson('/api/v1/auditoria?desde=ayer')->assertUnprocessable();
        $this->conToken($this->token)->getJson('/api/v1/auditoria?desde=2026-02-02T00:00:00Z&hasta=2026-01-01T00:00:00Z')->assertUnprocessable();
    }

    public function test_solo_la_ve_quien_tiene_el_permiso_de_auditoria(): void
    {
        $administradorDeUsuarios = $this->iniciarSesion($this->usuarioConPermisos(ClavePermiso::AdministrarUsuarios, ClavePermiso::AdministrarRoles, ClavePermiso::VerTodos));

        $this->conToken($administradorDeUsuarios)->getJson('/api/v1/auditoria')->assertForbidden();
        $this->sinCredencial()->getJson('/api/v1/auditoria')->assertUnauthorized();
    }
}
