<?php

namespace Tests\Feature\Identidad;

use App\Auditoria\Dominio\AccionAuditoria;
use App\Auditoria\Models\Auditoria;
use App\Identidad\Models\User;
use Laravel\Sanctum\PersonalAccessToken;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class AutenticacionTest extends TestCase
{
    private const MENSAJE_GENERICO = 'Las credenciales no son correctas.';

    /**
     * @return array<string, array{string}>
     */
    public static function credencialesInvalidas(): array
    {
        return [
            'contraseña equivocada' => ['equivocada'],
            'correo inexistente' => ['inexistente'],
            'cuenta inactiva' => ['inactiva'],
        ];
    }

    public function test_el_login_correcto_devuelve_un_token_con_su_expiracion(): void
    {
        $this->freezeTime();
        $usuario = $this->crearUsuario();

        $this->postJson('/api/v1/auth/login', ['email' => $usuario->email, 'password' => self::PASSWORD])
            ->assertOk()
            ->assertJsonStructure(['token', 'tipo_token', 'expira_en'])
            ->assertJsonPath('tipo_token', 'Bearer')
            ->assertJsonPath('expira_en', now()->addMinutes(480)->toIso8601String());
    }

    public function test_el_correo_se_normaliza_antes_de_comparar(): void
    {
        $usuario = $this->crearUsuario();

        $this->postJson('/api/v1/auth/login', ['email' => '  '.strtoupper($usuario->email).' ', 'password' => self::PASSWORD])
            ->assertOk();
    }

    public function test_el_token_en_la_base_de_datos_es_un_hash_y_no_el_valor_entregado(): void
    {
        $usuario = $this->crearUsuario();
        $entregado = $this->iniciarSesion($usuario);

        $guardado = PersonalAccessToken::sole()->token;

        $this->assertNotSame($entregado, $guardado);
        $this->assertSame(hash('sha256', explode('|', $entregado, 2)[1]), $guardado);
    }

    #[DataProvider('credencialesInvalidas')]
    public function test_cualquier_fallo_de_login_responde_lo_mismo(string $caso): void
    {
        $usuario = $this->crearUsuario(atributos: ['activo' => $caso !== 'inactiva']);
        $email = $caso === 'inexistente' ? 'nadie@empresa-ejemplo.com' : $usuario->email;
        $password = $caso === 'equivocada' ? 'no es la contraseña' : self::PASSWORD;

        $this->postJson('/api/v1/auth/login', ['email' => $email, 'password' => $password])
            ->assertUnprocessable()
            ->assertHeader('Content-Type', 'application/problem+json')
            ->assertJsonPath('type', 'urn:incidentes:problema:validacion')
            ->assertJsonPath('errors.email', [self::MENSAJE_GENERICO]);
    }

    public function test_un_login_fallido_deja_auditoria_sin_guardar_lo_escrito(): void
    {
        $usuario = $this->crearUsuario();

        $this->postJson('/api/v1/auth/login', ['email' => $usuario->email, 'password' => 'secreto mal escrito'])->assertUnprocessable();
        $this->postJson('/api/v1/auth/login', ['email' => 'nadie@empresa-ejemplo.com', 'password' => 'otro secreto'])->assertUnprocessable();

        $registros = Auditoria::orderBy('id')->get();

        $this->assertCount(2, $registros);
        $this->assertSame(AccionAuditoria::LoginFallido, $registros[0]->accion);
        $this->assertNull($registros[0]->usuario_id);
        $this->assertSame($usuario->id, $registros[0]->usuario_afectado_id);
        $this->assertNotNull($registros[0]->ip);
        $this->assertNull($registros[1]->usuario_afectado_id);
        $this->assertStringNotContainsString('secreto', $registros->toJson());
        $this->assertStringNotContainsString('nadie@', $registros->toJson());
    }

    public function test_un_login_correcto_no_deja_auditoria(): void
    {
        $this->iniciarSesion($this->crearUsuario());

        $this->assertSame(0, Auditoria::count());
    }

    public function test_el_login_se_limita_por_correo(): void
    {
        $usuario = $this->crearUsuario();
        $limite = (int) config('seguridad.limites.login_email');

        for ($intento = 1; $intento <= $limite; $intento++) {
            $this->postJson('/api/v1/auth/login', ['email' => $usuario->email, 'password' => 'mal'])->assertUnprocessable();
        }

        $this->postJson('/api/v1/auth/login', ['email' => $usuario->email, 'password' => self::PASSWORD])
            ->assertTooManyRequests()
            ->assertHeader('Content-Type', 'application/problem+json')
            ->assertHeader('Retry-After');
    }

    public function test_sin_credencial_la_ruta_protegida_responde_401(): void
    {
        $this->postJson('/api/v1/auth/logout')
            ->assertUnauthorized()
            ->assertHeader('WWW-Authenticate', 'Bearer')
            ->assertHeader('Content-Type', 'application/problem+json')
            ->assertJsonPath('status', 401);
    }

    public function test_un_token_inventado_responde_401(): void
    {
        $this->conToken('1|inventado')->postJson('/api/v1/auth/logout')->assertUnauthorized();
    }

    public function test_cerrar_sesion_revoca_solo_el_token_usado(): void
    {
        $usuario = $this->crearUsuario();
        $primero = $this->iniciarSesion($usuario);
        $segundo = $this->iniciarSesion($usuario);

        $this->conToken($primero)->postJson('/api/v1/auth/logout')->assertNoContent();

        $this->conToken($primero)->postJson('/api/v1/auth/logout')->assertUnauthorized();
        $this->conToken($segundo)->postJson('/api/v1/auth/logout')->assertNoContent();
    }

    public function test_un_token_vencido_deja_de_autenticar(): void
    {
        $token = $this->iniciarSesion($this->crearUsuario());

        $this->travel(481)->minutes();

        $this->conToken($token)->postJson('/api/v1/auth/logout')->assertUnauthorized();
    }

    public function test_una_cuenta_inactiva_no_obtiene_token(): void
    {
        $usuario = $this->crearUsuario(atributos: ['activo' => false]);

        $this->postJson('/api/v1/auth/login', ['email' => $usuario->email, 'password' => self::PASSWORD])->assertUnprocessable();

        $this->assertSame(0, User::find($usuario->id)->tokens()->count());
    }
}
