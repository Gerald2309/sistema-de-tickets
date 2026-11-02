<?php

namespace Tests\Feature\Identidad;

use App\Identidad\Dominio\ClavePermiso;
use App\Identidad\Models\Rol;
use App\Identidad\Models\User;
use Tests\TestCase;

class CambioObligatorioDeContrasenaTest extends TestCase
{
    private const INICIAL = 'Una contraseña inicial 2026';

    private const NUEVA = 'Mi propia contraseña 2026';

    private string $token;

    protected function setUp(): void
    {
        parent::setUp();

        $this->prepararDatosBase();
        $this->token = $this->iniciarSesion($this->usuarioConPermisos(ClavePermiso::AdministrarUsuarios));
    }

    private function crearDesdeAdministracion(): User
    {
        $id = $this->conToken($this->token)->postJson('/api/v1/usuarios', [
            'name' => 'Marta Ruiz',
            'email' => 'marta.ruiz@empresa-ejemplo.com',
            'password' => self::INICIAL,
            'rol_id' => Rol::where('nombre', 'Solicitante')->firstOrFail()->id,
        ])->assertCreated()->json('data.id');

        return User::findOrFail($id);
    }

    public function test_con_la_contrasena_inicial_solo_puede_cambiarla_y_despues_usa_el_sistema(): void
    {
        $persona = $this->crearDesdeAdministracion();
        $token = $this->iniciarSesion($persona, self::INICIAL);

        $this->conToken($token)->getJson('/api/v1/me')->assertOk()->assertJsonPath('data.debe_cambiar_contrasena', true);
        $this->conToken($token)->getJson('/api/v1/incidentes')->assertForbidden();
        $this->conToken($token)->getJson('/api/v1/perfil/sesiones')->assertOk();

        $this->conToken($token)->putJson('/api/v1/perfil/contrasena', ['password_actual' => self::INICIAL, 'password' => self::NUEVA])->assertOk();

        $this->conToken($token)->getJson('/api/v1/me')->assertJsonPath('data.debe_cambiar_contrasena', false);
        $this->conToken($token)->getJson('/api/v1/incidentes')->assertOk();
    }

    public function test_una_contrasena_restablecida_tambien_se_debe_cambiar(): void
    {
        $persona = $this->crearUsuario();

        $this->conToken($this->token)->patchJson("/api/v1/usuarios/{$persona->id}", ['password' => self::INICIAL])->assertOk();

        $this->assertTrue($persona->refresh()->debe_cambiar_contrasena);
    }

    public function test_cambiar_otros_datos_no_obliga_a_cambiarla(): void
    {
        $persona = $this->crearUsuario();

        $this->conToken($this->token)->patchJson("/api/v1/usuarios/{$persona->id}", ['name' => 'Otro nombre'])->assertOk();

        $this->assertFalse($persona->refresh()->debe_cambiar_contrasena);
    }

    public function test_la_nueva_debe_ser_distinta_de_la_actual(): void
    {
        $token = $this->iniciarSesion($this->crearDesdeAdministracion(), self::INICIAL);

        $this->conToken($token)->putJson('/api/v1/perfil/contrasena', ['password_actual' => self::INICIAL, 'password' => self::INICIAL])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['password' => 'La nueva contraseña debe ser distinta de la actual.'], 'errors');
    }
}
