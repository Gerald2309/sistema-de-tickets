<?php

namespace Tests;

use App\Identidad\Dominio\ClavePermiso;
use App\Identidad\Models\Permiso;
use App\Identidad\Models\Rol;
use App\Identidad\Models\User;
use App\Incidentes\Dominio\Categoria;
use App\Incidentes\Dominio\Estado;
use App\Incidentes\Dominio\Prioridad;
use App\Incidentes\Models\Incidente;
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
     * Usuario con un rol creado para la prueba, que tiene exactamente esos permisos.
     */
    protected function usuarioConPermisos(ClavePermiso ...$permisos): User
    {
        $rol = Rol::factory()->create();
        $rol->permisos()->sync(Permiso::whereIn('clave', array_map(fn (ClavePermiso $permiso) => $permiso->value, $permisos))->pluck('id'));

        return User::factory()->create(['password' => self::PASSWORD, 'rol_id' => $rol->id]);
    }

    /**
     * Incidente de un creador, con valores fijos para que la prueba no dependa del azar de la factory.
     *
     * @param  array<string, mixed>  $atributos
     */
    protected function incidenteDe(User $creador, array $atributos = []): Incidente
    {
        return Incidente::factory()->create($atributos + [
            'usuario_creador_id' => $creador->id,
            'usuario_asignado_id' => null,
            'estado' => Estado::Abierto,
            'prioridad' => Prioridad::Media,
            'categoria' => Categoria::Otros,
            'solucion' => null,
            'fecha_vencimiento' => now()->addDays(3),
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
