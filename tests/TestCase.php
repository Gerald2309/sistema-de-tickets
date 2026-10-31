<?php

namespace Tests;

use App\Identidad\Models\Rol;
use App\Identidad\Models\User;
use Database\Seeders\PermisosSeeder;
use Database\Seeders\RolesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use RefreshDatabase;

    protected const PASSWORD = 'una contraseña de pruebas bastante larga';

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PermisosSeeder::class);
        $this->seed(RolesSeeder::class);
    }

    /**
     * @param  array<string, mixed>  $atributos
     */
    protected function crearUsuario(string $rol = 'Solicitante', array $atributos = []): User
    {
        return User::factory()->create($atributos + [
            'password' => self::PASSWORD,
            'rol_id' => Rol::where('nombre', $rol)->firstOrFail()->id,
        ]);
    }

    /**
     * Inicia sesión con una petición real y devuelve el token en texto. Las pruebas usan tokens
     * reales (no Sanctum::actingAs) para poder comprobar logout y revocaciones.
     */
    protected function iniciarSesion(User $usuario, string $password = self::PASSWORD): string
    {
        $token = $this->postJson('/api/v1/auth/login', ['email' => $usuario->email, 'password' => $password])
            ->assertOk()
            ->json('token');

        $this->olvidarSesion();

        return $token;
    }

    /**
     * Petición autenticada con ese token. La aplicación de la prueba es una sola, así que se
     * descarta el usuario que la guarda de Sanctum guardó en la petición anterior.
     */
    protected function conToken(string $token): static
    {
        $this->olvidarSesion();

        return $this->withToken($token);
    }

    private function olvidarSesion(): void
    {
        $this->app['auth']->forgetGuards();
    }
}
