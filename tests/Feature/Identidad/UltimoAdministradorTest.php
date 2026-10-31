<?php

namespace Tests\Feature\Identidad;

use App\Auditoria\Models\Auditoria;
use App\Identidad\Dominio\ClavePermiso;
use App\Identidad\Models\Permiso;
use App\Identidad\Models\Rol;
use App\Identidad\Models\User;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

// Siempre queda al menos un usuario activo que administre roles y otro que administre usuarios.
class UltimoAdministradorTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->prepararDatosBase();
    }

    private function conProblemaDeUltimoAdministrador(TestResponse $respuesta): void
    {
        $respuesta->assertStatus(409)->assertJsonPath('type', 'urn:incidentes:problema:ultimo-administrador');
    }

    public function test_el_unico_administrador_no_puede_desactivarse(): void
    {
        $unico = $this->usuarioConPermisos(ClavePermiso::AdministrarUsuarios, ClavePermiso::AdministrarRoles);

        $this->conProblemaDeUltimoAdministrador(
            $this->conToken($this->iniciarSesion($unico))->patchJson("/api/v1/usuarios/{$unico->id}", ['activo' => false]),
        );

        $this->assertTrue($unico->fresh()->activo);
        $this->assertSame(0, Auditoria::where('usuario_afectado_id', $unico->id)->count());
    }

    public function test_puede_desactivarse_si_queda_otro_administrador_activo(): void
    {
        $uno = $this->usuarioConPermisos(ClavePermiso::AdministrarUsuarios, ClavePermiso::AdministrarRoles);
        $otro = $this->usuarioConPermisos(ClavePermiso::AdministrarUsuarios, ClavePermiso::AdministrarRoles);

        $this->conToken($this->iniciarSesion($uno))->patchJson("/api/v1/usuarios/{$otro->id}", ['activo' => false])->assertOk();
    }

    public function test_un_administrador_inactivo_no_cuenta(): void
    {
        $uno = $this->usuarioConPermisos(ClavePermiso::AdministrarUsuarios, ClavePermiso::AdministrarRoles);
        $this->usuarioConPermisos(ClavePermiso::AdministrarUsuarios, ClavePermiso::AdministrarRoles)->update(['activo' => false]);

        $this->conProblemaDeUltimoAdministrador(
            $this->conToken($this->iniciarSesion($uno))->patchJson("/api/v1/usuarios/{$uno->id}", ['activo' => false]),
        );
    }

    public function test_hacen_falta_los_dos_tipos_de_administrador(): void
    {
        $this->usuarioConPermisos(ClavePermiso::AdministrarRoles);
        $deUsuarios = $this->usuarioConPermisos(ClavePermiso::AdministrarUsuarios);

        $this->conProblemaDeUltimoAdministrador(
            $this->conToken($this->iniciarSesion($deUsuarios))->patchJson("/api/v1/usuarios/{$deUsuarios->id}", ['activo' => false]),
        );
        $this->assertTrue($deUsuarios->fresh()->activo);
    }

    public function test_cambiar_de_rol_al_unico_administrador_se_rechaza_y_se_revierte(): void
    {
        $unico = $this->usuarioConPermisos(ClavePermiso::AdministrarUsuarios, ClavePermiso::AdministrarRoles);
        $rolOriginal = $unico->rol_id;
        $solicitante = Rol::where('nombre', 'Solicitante')->firstOrFail();

        $this->conProblemaDeUltimoAdministrador(
            $this->conToken($this->iniciarSesion($unico))->putJson("/api/v1/usuarios/{$unico->id}/rol", ['rol_id' => $solicitante->id]),
        );

        $this->assertSame($rolOriginal, $unico->fresh()->rol_id);
        $this->assertSame(0, Auditoria::where('usuario_afectado_id', $unico->id)->count());
    }

    public function test_cambiar_de_rol_a_otro_con_los_mismos_permisos_si_se_permite(): void
    {
        $unico = $this->usuarioConPermisos(ClavePermiso::AdministrarUsuarios, ClavePermiso::AdministrarRoles);
        $equivalente = Rol::factory()->create();
        $equivalente->permisos()->sync($unico->rol->permisos->pluck('id'));

        $this->conToken($this->iniciarSesion($unico))->putJson("/api/v1/usuarios/{$unico->id}/rol", ['rol_id' => $equivalente->id])->assertOk();
    }

    public function test_quitar_un_permiso_de_administracion_al_rol_del_unico_administrador_se_rechaza_y_se_revierte(): void
    {
        $unico = $this->usuarioConPermisos(ClavePermiso::AdministrarUsuarios, ClavePermiso::AdministrarRoles, ClavePermiso::VerTodos);
        $token = $this->iniciarSesion($unico);

        foreach ([ClavePermiso::AdministrarRoles, ClavePermiso::AdministrarUsuarios] as $quitado) {
            $conservados = collect([ClavePermiso::AdministrarUsuarios, ClavePermiso::AdministrarRoles, ClavePermiso::VerTodos])
                ->reject(fn (ClavePermiso $permiso) => $permiso === $quitado)
                ->map(fn (ClavePermiso $permiso) => $permiso->value)->values()->all();

            $this->conProblemaDeUltimoAdministrador(
                $this->conToken($token)->putJson("/api/v1/roles/{$unico->rol_id}/permisos", ['permisos' => $conservados]),
            );
        }

        $this->assertCount(3, $unico->rol->permisos()->get());
        $this->assertSame(0, Auditoria::count());
    }

    public function test_quitar_permisos_que_no_son_de_administracion_si_se_permite(): void
    {
        $unico = $this->usuarioConPermisos(ClavePermiso::AdministrarUsuarios, ClavePermiso::AdministrarRoles, ClavePermiso::VerTodos);

        $this->conToken($this->iniciarSesion($unico))->putJson("/api/v1/roles/{$unico->rol_id}/permisos", ['permisos' => ['administrar_usuarios', 'administrar_roles']])->assertOk();
    }

    public function test_quitar_el_permiso_a_un_rol_sin_usuarios_administradores_no_afecta(): void
    {
        $administrador = $this->usuarioConPermisos(ClavePermiso::AdministrarUsuarios, ClavePermiso::AdministrarRoles);
        $otroRol = Rol::factory()->create();
        $otroRol->permisos()->sync(Permiso::where('clave', ClavePermiso::AdministrarRoles->value)->pluck('id'));

        $this->conToken($this->iniciarSesion($administrador))->putJson("/api/v1/roles/{$otroRol->id}/permisos", ['permisos' => []])->assertOk();
        $this->assertInstanceOf(User::class, $administrador);
    }
}
