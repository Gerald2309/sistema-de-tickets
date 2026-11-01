<?php

namespace Tests\Feature\Identidad;

use App\Identidad\Models\User;
use App\Incidentes\Dominio\Estado;
use App\Incidentes\Dominio\Prioridad;
use App\Incidentes\Models\AcuerdoDeServicio;
use App\Incidentes\Models\Incidente;
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

    public function test_los_incidentes_de_demostracion_siguen_los_plazos_de_su_empresa(): void
    {
        config(['seguridad.administrador' => ['nombre' => 'Administración', 'email' => 'admin@empresa-ejemplo.com', 'password' => self::PASSWORD]]);
        $this->seed([AdministradorSeeder::class, DatosDemoSeeder::class]);

        $this->assertGreaterThan(0, AcuerdoDeServicio::count());

        // Las críticas de quien no tiene acuerdo propio vencen a las 2 horas de crearse.
        Incidente::query()->withoutEagerLoads()
            ->where('prioridad', Prioridad::Critica)
            ->whereNotIn('cliente_id', AcuerdoDeServicio::query()->select('cliente_id'))
            ->get()
            ->each(fn (Incidente $incidente) => $this->assertEquals($incidente->created_at?->copy()->addHours(2), $incidente->fecha_vencimiento));

        $resueltos = Incidente::whereNotNull('resuelto_en')->count();
        $aTiempo = Incidente::whereNotNull('resuelto_en')->whereColumn('resuelto_en', '<=', 'fecha_vencimiento')->count();
        $this->assertGreaterThan(0.6, $aTiempo / $resueltos, 'La mayoría se resuelve dentro del plazo.');

        $pendientes = Incidente::whereIn('estado', [Estado::Abierto, Estado::EnProgreso])->count();
        $vencidos = Incidente::query()->vencidosAl(now())->count();
        $this->assertLessThan(0.5, $vencidos / $pendientes, 'Lo vencido es la excepción.');
    }
}
