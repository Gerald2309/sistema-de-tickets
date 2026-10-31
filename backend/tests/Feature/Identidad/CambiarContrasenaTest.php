<?php

namespace Tests\Feature\Identidad;

use App\Auditoria\Dominio\AccionAuditoria;
use App\Auditoria\Models\Auditoria;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class CambiarContrasenaTest extends TestCase
{
    private const NUEVA = 'otra contraseña distinta y larga';

    /**
     * @return array<string, array{string}>
     */
    public static function contrasenasAceptadas(): array
    {
        return [
            'justo 15 caracteres, solo minúsculas' => ['abcdefghijklmno'],
            'frase con espacios' => ['la impresora del segundo piso'],
            '64 caracteres' => [str_repeat('a1', 32)],
            '256 caracteres' => [str_repeat('x', 256)],
        ];
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function contrasenasRechazadas(): array
    {
        return [
            '14 caracteres' => [str_repeat('a', 14), 'al menos 15'],
            '257 caracteres' => [str_repeat('x', 257), 'superar los 256'],
        ];
    }

    public function test_cambia_la_contrasena_y_la_anterior_deja_de_servir(): void
    {
        $usuario = $this->crearUsuario();
        $token = $this->iniciarSesion($usuario);

        $this->conToken($token)->putJson('/api/v1/perfil/contrasena', ['password_actual' => self::PASSWORD, 'password' => self::NUEVA])
            ->assertOk();

        $this->postJson('/api/v1/auth/login', ['email' => $usuario->email, 'password' => self::PASSWORD])->assertUnprocessable();
        $this->postJson('/api/v1/auth/login', ['email' => $usuario->email, 'password' => self::NUEVA])->assertOk();
    }

    public function test_revoca_las_demas_sesiones_pero_conserva_la_actual(): void
    {
        $usuario = $this->crearUsuario();
        $actual = $this->iniciarSesion($usuario);
        $otra = $this->iniciarSesion($usuario);

        $this->conToken($actual)->putJson('/api/v1/perfil/contrasena', ['password_actual' => self::PASSWORD, 'password' => self::NUEVA])
            ->assertOk();

        $this->conToken($otra)->postJson('/api/v1/auth/logout')->assertUnauthorized();
        $this->conToken($actual)->postJson('/api/v1/auth/logout')->assertNoContent();
    }

    public function test_deja_constancia_en_la_auditoria(): void
    {
        $usuario = $this->crearUsuario();
        $token = $this->iniciarSesion($usuario);

        $this->conToken($token)->putJson('/api/v1/perfil/contrasena', ['password_actual' => self::PASSWORD, 'password' => self::NUEVA])
            ->assertOk();

        $registro = Auditoria::sole();

        $this->assertSame(AccionAuditoria::ContrasenaCambiada, $registro->accion);
        $this->assertSame($usuario->id, $registro->usuario_id);
        $this->assertSame($usuario->id, $registro->usuario_afectado_id);
        $this->assertStringNotContainsString(self::NUEVA, $registro->toJson());
    }

    public function test_exige_la_contrasena_actual_correcta(): void
    {
        $usuario = $this->crearUsuario();
        $token = $this->iniciarSesion($usuario);

        $this->conToken($token)->putJson('/api/v1/perfil/contrasena', ['password_actual' => 'no es esa', 'password' => self::NUEVA])
            ->assertUnprocessable()
            ->assertJsonPath('errors.password_actual', ['La contraseña actual no es correcta.']);

        $this->postJson('/api/v1/auth/login', ['email' => $usuario->email, 'password' => self::PASSWORD])->assertOk();
        $this->assertSame(0, Auditoria::count());
    }

    #[DataProvider('contrasenasAceptadas')]
    public function test_acepta_contrasenas_largas_sin_reglas_de_composicion(string $nueva): void
    {
        $token = $this->iniciarSesion($this->crearUsuario());

        $this->conToken($token)->putJson('/api/v1/perfil/contrasena', ['password_actual' => self::PASSWORD, 'password' => $nueva])
            ->assertOk();
    }

    #[DataProvider('contrasenasRechazadas')]
    public function test_rechaza_contrasenas_fuera_de_los_limites_con_un_mensaje_claro(string $nueva, string $fragmento): void
    {
        $token = $this->iniciarSesion($this->crearUsuario());

        $respuesta = $this->conToken($token)->putJson('/api/v1/perfil/contrasena', ['password_actual' => self::PASSWORD, 'password' => $nueva])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('password');

        $this->assertStringContainsString($fragmento, $respuesta->json('errors.password.0'));
    }
}
