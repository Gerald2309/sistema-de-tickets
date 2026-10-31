<?php

namespace Tests\Feature\Identidad;

use App\Identidad\Models\User;
use Database\Seeders\AdministradorSeeder;
use Database\Seeders\DatosDemoSeeder;
use Tests\TestCase;

class CuentasDePruebaTest extends TestCase
{
    private const DEMOSTRACION = 'una contraseña de demostración común';

    public function test_la_demostracion_deja_una_cuenta_de_prueba_por_perfil_con_la_contrasena_comun(): void
    {
        config([
            'seguridad.demostracion.password' => self::DEMOSTRACION,
            'seguridad.administrador' => ['nombre' => 'Administración', 'email' => 'admin@empresa-ejemplo.com', 'password' => self::PASSWORD],
        ]);
        $this->seed([AdministradorSeeder::class, DatosDemoSeeder::class]);

        $perfiles = [
            'cristina.guillen@empresa-ejemplo.com' => 'Soporte N1',
            'diego.betancourt@empresa-ejemplo.com' => 'Soporte N2',
            'valentina.ojeda@empresa-ejemplo.com' => 'Soporte N3',
            'ursula.apodaca@empresa-ejemplo.com' => 'Solicitante',
        ];
        foreach ($perfiles as $correo => $rol) {
            $this->postJson('/api/v1/auth/login', ['email' => $correo, 'password' => self::DEMOSTRACION])->assertOk();
            $this->assertSame($rol, User::where('email', $correo)->sole()->rol->nombre);
        }
    }
}
