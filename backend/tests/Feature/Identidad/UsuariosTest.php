<?php

namespace Tests\Feature\Identidad;

use App\Auditoria\Dominio\AccionAuditoria;
use App\Auditoria\Models\Auditoria;
use App\Identidad\Dominio\ClavePermiso;
use App\Identidad\Models\Rol;
use App\Identidad\Models\User;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UsuariosTest extends TestCase
{
    private User $administrador;

    private string $token;

    protected function setUp(): void
    {
        parent::setUp();

        $this->prepararDatosBase();
        $this->administrador = $this->usuarioConPermisos(ClavePermiso::AdministrarUsuarios, ClavePermiso::AdministrarRoles);
        $this->token = $this->iniciarSesion($this->administrador);
    }

    private function auditoria(AccionAuditoria $accion): ?Auditoria
    {
        return Auditoria::where('accion', $accion->value)->latest('id')->first();
    }

    public function test_crea_un_usuario_normaliza_el_correo_y_lo_audita(): void
    {
        $rol = Rol::where('nombre', 'Solicitante')->firstOrFail();

        $respuesta = $this->conToken($this->token)->postJson('/api/v1/usuarios', [
            'name' => 'Marta Ruiz',
            'email' => '  Marta.Ruiz@Empresa-Ejemplo.com ',
            'password' => 'Una contraseña inicial 2026',
            'rol_id' => $rol->id,
        ])->assertCreated();

        $respuesta->assertJsonPath('data.email', 'marta.ruiz@empresa-ejemplo.com')
            ->assertJsonPath('data.activo', true)
            ->assertJsonPath('data.rol.nombre', 'Solicitante')
            ->assertJsonMissingPath('data.password');

        $usuario = User::findOrFail($respuesta->json('data.id'));
        $this->assertTrue(Hash::check('Una contraseña inicial 2026', $usuario->password));
        $this->assertNotNull($this->iniciarSesion($usuario, 'Una contraseña inicial 2026'));

        $registro = $this->auditoria(AccionAuditoria::UsuarioCreado);
        $this->assertSame($this->administrador->id, $registro->usuario_id);
        $this->assertSame($usuario->id, $registro->usuario_afectado_id);
        $this->assertSame('Rol «Solicitante»', $registro->detalle);
        $this->assertNotNull($registro->ip);
    }

    public function test_rechaza_datos_invalidos_al_crear(): void
    {
        $existente = $this->crearUsuario();

        $this->conToken($this->token)->postJson('/api/v1/usuarios', [
            'name' => '',
            'email' => strtoupper($existente->email),
            'password' => 'corta',
            'rol_id' => 9999,
        ])->assertUnprocessable()->assertJsonValidationErrors(['name', 'email', 'password', 'rol_id'], 'errors');

        $this->assertNull($this->auditoria(AccionAuditoria::UsuarioCreado));
    }

    public function test_lista_con_filtros_y_paginacion(): void
    {
        $soporte = Rol::where('nombre', 'Soporte N1')->firstOrFail();
        $this->crearUsuario('Soporte N1', ['name' => 'Ana Torres']);
        $this->crearUsuario('Soporte N1', ['name' => 'Luis Anaya', 'activo' => false]);
        $this->crearUsuario('Solicitante', ['name' => 'Pedro Gómez']);

        $this->conToken($this->token)->getJson('/api/v1/usuarios?q=ana&rol_id='.$soporte->id)
            ->assertOk()->assertJsonCount(2, 'data');
        $this->conToken($this->token)->getJson('/api/v1/usuarios?q=ana&activo=0')
            ->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.name', 'Luis Anaya');
        $this->conToken($this->token)->getJson('/api/v1/usuarios?per_page=2')
            ->assertOk()->assertJsonCount(2, 'data')->assertJsonPath('meta.total', 4);
        $this->conToken($this->token)->getJson('/api/v1/usuarios?per_page=101')->assertUnprocessable();
    }

    public function test_el_comodin_de_la_busqueda_no_se_interpreta(): void
    {
        $this->crearUsuario('Solicitante', ['name' => 'Ana Torres']);

        $this->conToken($this->token)->getJson('/api/v1/usuarios?q=%25')->assertOk()->assertJsonCount(0, 'data');
    }

    public function test_muestra_un_usuario_y_responde_404_si_no_existe(): void
    {
        $usuario = $this->crearUsuario();

        $this->conToken($this->token)->getJson("/api/v1/usuarios/{$usuario->id}")
            ->assertOk()->assertJsonPath('data.email', $usuario->email);
        $this->conToken($this->token)->getJson('/api/v1/usuarios/999999')->assertNotFound();
    }

    public function test_modifica_nombre_y_correo_y_audita_los_campos(): void
    {
        $usuario = $this->crearUsuario();

        $this->conToken($this->token)->patchJson("/api/v1/usuarios/{$usuario->id}", ['name' => 'Nombre Nuevo', 'email' => 'Nuevo@Empresa-Ejemplo.com'])
            ->assertOk()->assertJsonPath('data.name', 'Nombre Nuevo')->assertJsonPath('data.email', 'nuevo@empresa-ejemplo.com');

        $this->assertSame('Campos: name, email', $this->auditoria(AccionAuditoria::UsuarioModificado)->detalle);
    }

    public function test_enviar_los_mismos_valores_no_deja_registro(): void
    {
        $usuario = $this->crearUsuario();

        $this->conToken($this->token)->patchJson("/api/v1/usuarios/{$usuario->id}", ['name' => $usuario->name, 'activo' => true])->assertOk();

        $this->assertSame(0, Auditoria::where('usuario_afectado_id', $usuario->id)->count());
    }

    public function test_el_correo_no_puede_repetirse_pero_si_conservarse(): void
    {
        $otro = $this->crearUsuario();
        $usuario = $this->crearUsuario();

        $this->conToken($this->token)->patchJson("/api/v1/usuarios/{$usuario->id}", ['email' => $otro->email])
            ->assertUnprocessable()->assertJsonValidationErrors(['email'], 'errors');
        $this->conToken($this->token)->patchJson("/api/v1/usuarios/{$usuario->id}", ['email' => $usuario->email])->assertOk();
    }

    public function test_desactivar_cierra_sus_sesiones_y_le_impide_entrar_y_reactivar_se_audita(): void
    {
        $usuario = $this->crearUsuario();
        $tokenDelUsuario = $this->iniciarSesion($usuario);

        $this->conToken($this->token)->patchJson("/api/v1/usuarios/{$usuario->id}", ['activo' => false])
            ->assertOk()->assertJsonPath('data.activo', false);

        $this->assertSame(0, $usuario->tokens()->count());
        $this->assertNotNull($this->auditoria(AccionAuditoria::UsuarioDesactivado));
        $this->sinCredencial()->postJson('/api/v1/auth/login', ['email' => $usuario->email, 'password' => self::PASSWORD])->assertUnprocessable();
        $this->assertNotEmpty($tokenDelUsuario);

        $this->conToken($this->token)->patchJson("/api/v1/usuarios/{$usuario->id}", ['activo' => true])->assertOk();
        $this->assertNotNull($this->auditoria(AccionAuditoria::UsuarioReactivado));
        $this->assertNotNull($this->iniciarSesion($usuario));
    }

    public function test_restablece_la_contrasena_de_otra_persona_y_cierra_sus_sesiones(): void
    {
        $usuario = $this->crearUsuario();
        $this->iniciarSesion($usuario);

        $this->conToken($this->token)->patchJson("/api/v1/usuarios/{$usuario->id}", ['password' => 'Contraseña provisional 2026'])->assertOk();

        $this->assertSame(0, $usuario->tokens()->count());
        $this->assertNotNull($this->iniciarSesion($usuario, 'Contraseña provisional 2026'));
        $registro = $this->auditoria(AccionAuditoria::ContrasenaRestablecida);
        $this->assertSame($usuario->id, $registro->usuario_afectado_id);
        $this->assertNull($registro->detalle);
    }

    public function test_la_contrasena_nueva_debe_cumplir_las_reglas(): void
    {
        $usuario = $this->crearUsuario();

        $this->conToken($this->token)->patchJson("/api/v1/usuarios/{$usuario->id}", ['password' => 'corta'])
            ->assertUnprocessable()->assertJsonValidationErrors(['password'], 'errors');
    }

    public function test_no_se_restablece_la_contrasena_propia_por_aqui(): void
    {
        $this->conToken($this->token)->patchJson("/api/v1/usuarios/{$this->administrador->id}", ['password' => 'Contraseña provisional 2026'])
            ->assertUnprocessable()->assertJsonPath('errors.password.0', trans('identidad.password_propia'));

        $this->assertTrue(Hash::check(self::PASSWORD, $this->administrador->fresh()->password));
    }

    public function test_asigna_un_rol_y_lo_audita_solo_si_cambia(): void
    {
        $usuario = $this->crearUsuario();
        $soporte = Rol::where('nombre', 'Soporte N2')->firstOrFail();

        $this->conToken($this->token)->putJson("/api/v1/usuarios/{$usuario->id}/rol", ['rol_id' => $soporte->id])
            ->assertOk()->assertJsonPath('data.rol.nombre', 'Soporte N2');
        $this->assertSame('Rol «Soporte N2»', $this->auditoria(AccionAuditoria::RolAsignado)->detalle);

        $this->conToken($this->token)->putJson("/api/v1/usuarios/{$usuario->id}/rol", ['rol_id' => $soporte->id])->assertOk();
        $this->assertSame(1, Auditoria::where('accion', AccionAuditoria::RolAsignado->value)->count());

        $this->conToken($this->token)->putJson("/api/v1/usuarios/{$usuario->id}/rol", ['rol_id' => 9999])->assertUnprocessable();
    }

    public function test_quien_solo_administra_usuarios_no_asigna_permisos_de_administracion_que_le_faltan(): void
    {
        $gestor = $this->usuarioConPermisos(ClavePermiso::AdministrarUsuarios, ClavePermiso::VerTodos);
        $token = $this->iniciarSesion($gestor);
        $administrador = Rol::where('nombre', 'Administrador')->firstOrFail();
        $usuario = $this->crearUsuario();

        $this->conToken($token)->putJson("/api/v1/usuarios/{$gestor->id}/rol", ['rol_id' => $administrador->id])
            ->assertForbidden()->assertJsonPath('type', 'urn:incidentes:problema:rol-fuera-de-alcance');
        $this->conToken($token)->postJson('/api/v1/usuarios', [
            'name' => 'Nueva', 'email' => 'nueva@ejemplo.com', 'password' => 'Una-Contrasena-Larga-2026!', 'rol_id' => $administrador->id,
        ])->assertForbidden();
        $this->assertNotSame($administrador->id, $gestor->fresh()->rol_id);
        $this->assertNull(User::where('email', 'nueva@ejemplo.com')->first());

        // Un rol sin permisos de administración se asigna con normalidad.
        $soporte = Rol::where('nombre', 'Soporte N2')->firstOrFail();
        $this->conToken($token)->putJson("/api/v1/usuarios/{$usuario->id}/rol", ['rol_id' => $soporte->id])->assertOk();
    }

    public function test_exige_el_permiso_de_administrar_usuarios(): void
    {
        $usuario = $this->crearUsuario();
        $sinPermiso = $this->iniciarSesion($this->usuarioConPermisos(ClavePermiso::AdministrarRoles, ClavePermiso::VerTodos));

        foreach ([
            ['getJson', '/api/v1/usuarios'],
            ['postJson', '/api/v1/usuarios'],
            ['getJson', "/api/v1/usuarios/{$usuario->id}"],
            ['patchJson', "/api/v1/usuarios/{$usuario->id}"],
            ['putJson', "/api/v1/usuarios/{$usuario->id}/rol"],
        ] as [$metodo, $ruta]) {
            $this->conToken($sinPermiso)->{$metodo}($ruta)->assertForbidden();
        }
    }
}
