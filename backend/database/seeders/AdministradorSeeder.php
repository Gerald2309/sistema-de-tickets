<?php

namespace Database\Seeders;

use App\Identidad\Models\Rol;
use App\Identidad\Models\User;
use Illuminate\Database\Seeder;
use RuntimeException;

class AdministradorSeeder extends Seeder
{
    private const LONGITUD_MINIMA_PASSWORD = 15;

    public function run(): void
    {
        $datos = config('seguridad.administrador');

        foreach (['nombre' => 'ADMIN_NOMBRE', 'email' => 'ADMIN_EMAIL', 'password' => 'ADMIN_PASSWORD'] as $campo => $variable) {
            if (blank($datos[$campo])) {
                throw new RuntimeException("Falta $variable en el archivo .env para crear el primer administrador.");
            }
        }

        if (mb_strlen($datos['password']) < self::LONGITUD_MINIMA_PASSWORD) {
            throw new RuntimeException('ADMIN_PASSWORD debe tener al menos '.self::LONGITUD_MINIMA_PASSWORD.' caracteres.');
        }

        User::firstOrCreate(
            ['email' => $datos['email']],
            [
                'name' => $datos['nombre'],
                'password' => $datos['password'],
                'activo' => true,
                'rol_id' => Rol::where('nombre', 'Administrador')->firstOrFail()->id,
            ],
        );
    }
}
