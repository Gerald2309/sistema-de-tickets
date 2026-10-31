<?php

namespace Tests\Feature\Identidad;

use App\Auditoria\Dominio\AccionAuditoria;
use App\Auditoria\Models\Auditoria;
use App\Identidad\Dominio\ClavePermiso;
use App\Identidad\Models\Rol;
use Tests\TestCase;

class RolesTest extends TestCase
{
    private string $token;

    protected function setUp(): void
    {
        parent::setUp();

        $this->prepararDatosBase();
        $this->token = $this->iniciarSesion($this->usuarioConPermisos(ClavePermiso::AdministrarRoles, ClavePermiso::AdministrarUsuarios));
    }

    /**
     * @return list<string>
     */
    private function detalles(AccionAuditoria $accion): array
    {
        return Auditoria::where('accion', $accion->value)->orderBy('id')->pluck('detalle')->all();
    }

    public function test_lista_los_roles_con_sus_permisos_y_cuantos_usuarios_los_tienen(): void
    {
        $this->crearUsuario('Solicitante');
        $this->crearUsuario('Solicitante');

        $respuesta = $this->conToken($this->token)->getJson('/api/v1/roles')->assertOk();

        $solicitante = collect($respuesta->json('data'))->firstWhere('nombre', 'Solicitante');
        $this->assertSame(2, $solicitante['usuarios']);
        $this->assertSame(['crear_incidente', 'editar_propios', 'ver_dashboard', 'ver_historial', 'ver_propios'], $solicitante['permisos']);
    }

    public function test_muestra_un_rol_y_responde_404_si_no_existe(): void
    {
        $rol = Rol::where('nombre', 'Soporte N1')->firstOrFail();

        $this->conToken($this->token)->getJson("/api/v1/roles/{$rol->id}")
            ->assertOk()->assertJsonPath('data.nombre', 'Soporte N1')->assertJsonCount(7, 'data.permisos');
        $this->conToken($this->token)->getJson('/api/v1/roles/999999')->assertNotFound();
    }

    public function test_el_catalogo_de_permisos_sale_del_enum(): void
    {
        $respuesta = $this->conToken($this->token)->getJson('/api/v1/permisos')->assertOk();

        $this->assertSame(
            array_map(fn (ClavePermiso $permiso) => $permiso->value, ClavePermiso::cases()),
            array_column($respuesta->json('data'), 'clave'),
        );
    }

    public function test_quien_solo_administra_usuarios_consulta_roles_pero_no_los_modifica(): void
    {
        $token = $this->iniciarSesion($this->usuarioConPermisos(ClavePermiso::AdministrarUsuarios));
        $rol = Rol::where('nombre', 'Solicitante')->firstOrFail();

        $this->conToken($token)->getJson('/api/v1/roles')->assertOk();
        $this->conToken($token)->getJson("/api/v1/roles/{$rol->id}")->assertOk();
        $this->conToken($token)->getJson('/api/v1/permisos')->assertOk();
        $this->conToken($token)->postJson('/api/v1/roles', ['nombre' => 'Nuevo'])->assertForbidden();
        $this->conToken($token)->patchJson("/api/v1/roles/{$rol->id}", ['nombre' => 'Otro'])->assertForbidden();
        $this->conToken($token)->deleteJson("/api/v1/roles/{$rol->id}")->assertForbidden();
        $this->conToken($token)->putJson("/api/v1/roles/{$rol->id}/permisos", ['permisos' => []])->assertForbidden();
    }

    public function test_quien_no_administra_nada_no_ve_roles(): void
    {
        $token = $this->iniciarSesion($this->usuarioConPermisos(ClavePermiso::VerTodos));

        $this->conToken($token)->getJson('/api/v1/roles')->assertForbidden();
        $this->conToken($token)->getJson('/api/v1/permisos')->assertForbidden();
    }

    public function test_crea_un_rol_sin_permisos_y_lo_audita(): void
    {
        $this->conToken($this->token)->postJson('/api/v1/roles', ['nombre' => '  Auditor externo '])
            ->assertCreated()->assertJsonPath('data.nombre', 'Auditor externo')->assertJsonPath('data.permisos', [])->assertJsonPath('data.usuarios', 0);

        $this->assertSame(['Rol «Auditor externo»'], $this->detalles(AccionAuditoria::RolCreado));
    }

    public function test_el_nombre_del_rol_es_obligatorio_y_unico(): void
    {
        $this->conToken($this->token)->postJson('/api/v1/roles', ['nombre' => ''])->assertUnprocessable();
        $this->conToken($this->token)->postJson('/api/v1/roles', ['nombre' => 'solicitante'])
            ->assertUnprocessable()->assertJsonValidationErrors(['nombre'], 'errors');
    }

    public function test_renombra_un_rol_y_conservar_el_nombre_no_deja_registro(): void
    {
        $rol = Rol::factory()->create(['nombre' => 'Temporal']);

        $this->conToken($this->token)->patchJson("/api/v1/roles/{$rol->id}", ['nombre' => 'Temporal'])->assertOk();
        $this->assertSame([], $this->detalles(AccionAuditoria::RolRenombrado));

        $this->conToken($this->token)->patchJson("/api/v1/roles/{$rol->id}", ['nombre' => 'Definitivo'])
            ->assertOk()->assertJsonPath('data.nombre', 'Definitivo');
        $this->assertSame(['«Temporal» pasó a «Definitivo»'], $this->detalles(AccionAuditoria::RolRenombrado));
    }

    public function test_elimina_un_rol_sin_usuarios_y_lo_audita(): void
    {
        $rol = Rol::factory()->create(['nombre' => 'Temporal']);

        $this->conToken($this->token)->deleteJson("/api/v1/roles/{$rol->id}")->assertNoContent();

        $this->assertModelMissing($rol);
        $this->assertSame(['Rol «Temporal»'], $this->detalles(AccionAuditoria::RolEliminado));
    }

    public function test_no_elimina_un_rol_en_uso(): void
    {
        $rol = Rol::where('nombre', 'Solicitante')->firstOrFail();
        $this->crearUsuario('Solicitante');

        $this->conToken($this->token)->deleteJson("/api/v1/roles/{$rol->id}")
            ->assertStatus(409)->assertJsonPath('type', 'urn:incidentes:problema:rol-en-uso');

        $this->assertModelExists($rol);
        $this->assertSame([], $this->detalles(AccionAuditoria::RolEliminado));
    }

    public function test_fija_el_conjunto_de_permisos_y_audita_cada_cambio(): void
    {
        $rol = Rol::factory()->create(['nombre' => 'Auditor externo']);

        $this->conToken($this->token)->putJson("/api/v1/roles/{$rol->id}/permisos", ['permisos' => ['ver_todos', 'ver_historial']])
            ->assertOk()->assertJsonPath('data.permisos', ['ver_historial', 'ver_todos']);
        $this->assertEqualsCanonicalizing(
            ['Rol «Auditor externo»: ver_todos', 'Rol «Auditor externo»: ver_historial'],
            $this->detalles(AccionAuditoria::PermisoConcedido),
        );

        $this->conToken($this->token)->putJson("/api/v1/roles/{$rol->id}/permisos", ['permisos' => ['ver_todos', 'ver_dashboard']])
            ->assertOk()->assertJsonPath('data.permisos', ['ver_dashboard', 'ver_todos']);
        $this->assertSame(['Rol «Auditor externo»: ver_historial'], $this->detalles(AccionAuditoria::PermisoRetirado));
        $this->assertCount(3, $this->detalles(AccionAuditoria::PermisoConcedido));
    }

    public function test_repetir_el_mismo_conjunto_no_deja_registro(): void
    {
        $rol = Rol::where('nombre', 'Soporte N1')->firstOrFail();
        $actuales = $rol->permisos->map(fn ($permiso) => $permiso->clave->value)->all();

        $this->conToken($this->token)->putJson("/api/v1/roles/{$rol->id}/permisos", ['permisos' => $actuales])->assertOk();

        $this->assertSame(0, Auditoria::whereIn('accion', [AccionAuditoria::PermisoConcedido->value, AccionAuditoria::PermisoRetirado->value])->count());
    }

    public function test_un_rol_puede_quedar_sin_permisos_pero_la_lista_es_obligatoria(): void
    {
        $rol = Rol::where('nombre', 'Soporte N3')->firstOrFail();

        $this->conToken($this->token)->putJson("/api/v1/roles/{$rol->id}/permisos", [])->assertUnprocessable();
        $this->conToken($this->token)->putJson("/api/v1/roles/{$rol->id}/permisos", ['permisos' => []])
            ->assertOk()->assertJsonPath('data.permisos', []);
    }

    public function test_rechaza_permisos_desconocidos_o_repetidos(): void
    {
        $rol = Rol::where('nombre', 'Soporte N3')->firstOrFail();

        $this->conToken($this->token)->putJson("/api/v1/roles/{$rol->id}/permisos", ['permisos' => ['volar']])->assertUnprocessable();
        $this->conToken($this->token)->putJson("/api/v1/roles/{$rol->id}/permisos", ['permisos' => ['ver_todos', 'ver_todos']])->assertUnprocessable();
    }

    public function test_los_cambios_de_permisos_rigen_en_la_siguiente_peticion(): void
    {
        $rol = Rol::factory()->create();
        $usuario = $this->crearUsuario();
        $usuario->update(['rol_id' => $rol->id]);
        $token = $this->iniciarSesion($usuario);

        $this->conToken($token)->getJson('/api/v1/dashboard')->assertForbidden();
        $this->conToken($this->token)->putJson("/api/v1/roles/{$rol->id}/permisos", ['permisos' => ['ver_dashboard']])->assertOk();
        $this->conToken($token)->getJson('/api/v1/dashboard')->assertOk();
    }
}
