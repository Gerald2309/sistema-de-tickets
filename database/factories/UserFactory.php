<?php

namespace Database\Factories;

use App\Models\Rol;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    private const DOMINIO = 'empresa-ejemplo.com';

    /** @var array<string, int> */
    private static array $correosUsados = [];

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $nombre = fake()->firstName();
        $apellido = fake()->lastName();

        return [
            'name' => "$nombre $apellido",
            'email' => self::correo($nombre, $apellido),
            // Contraseña aleatoria: las cuentas de demostración no sirven para iniciar sesión.
            'password' => Str::random(40),
            'activo' => true,
            'rol_id' => Rol::factory(),
        ];
    }

    public function inactivo(): static
    {
        return $this->state(fn () => ['activo' => false]);
    }

    /**
     * Correo corporativo nombre.apellido@dominio; si ya existe, se numera (nombre.apellido2).
     */
    private static function correo(string $nombre, string $apellido): string
    {
        $base = preg_replace('/[^a-z.]/', '', Str::lower(Str::ascii("$nombre.$apellido")));
        $repeticiones = self::$correosUsados[$base] = (self::$correosUsados[$base] ?? 0) + 1;

        return ($repeticiones > 1 ? $base.$repeticiones : $base).'@'.self::DOMINIO;
    }
}
