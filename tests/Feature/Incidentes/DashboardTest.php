<?php

namespace Tests\Feature\Incidentes;

use App\Identidad\Dominio\ClavePermiso;
use App\Incidentes\Dominio\Estado;
use App\Incidentes\Dominio\Prioridad;
use Illuminate\Support\Facades\DB;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->prepararDatosBase();
        $this->freezeTime();
    }

    private function dashboard(string $token): TestResponse
    {
        return $this->conToken($token)->getJson('/api/v1/dashboard');
    }

    public function test_cuenta_total_por_estado_por_prioridad_y_vencidos(): void
    {
        $creador = $this->crearUsuario();
        $this->incidenteDe($creador, ['estado' => Estado::Abierto, 'prioridad' => Prioridad::Alta, 'fecha_vencimiento' => now()->subDay()]);
        $this->incidenteDe($creador, ['estado' => Estado::EnProgreso, 'prioridad' => Prioridad::Alta, 'fecha_vencimiento' => now()->subMinute()]);
        $this->incidenteDe($creador, ['estado' => Estado::Abierto, 'prioridad' => Prioridad::Baja]);
        // Resuelto y cerrado no vencen aunque pase la fecha.
        $this->incidenteDe($creador, ['estado' => Estado::Resuelto, 'solucion' => 'Listo.', 'fecha_vencimiento' => now()->subDays(5)]);
        $this->incidenteDe($creador, ['estado' => Estado::Cerrado, 'solucion' => 'Listo.', 'prioridad' => Prioridad::Critica, 'fecha_vencimiento' => now()->subDays(5)]);
        $token = $this->iniciarSesion($this->usuarioConPermisos(ClavePermiso::VerDashboard, ClavePermiso::VerTodos));

        $this->dashboard($token)->assertOk()->assertExactJson(['data' => [
            'total' => 5,
            'por_estado' => ['abierto' => 2, 'en_progreso' => 1, 'resuelto' => 1, 'cerrado' => 1],
            'por_prioridad' => ['baja' => 1, 'media' => 1, 'alta' => 2, 'critica' => 1],
            'vencidos' => 2,
        ]]);
    }

    public function test_un_vencimiento_exactamente_ahora_todavia_no_esta_vencido(): void
    {
        $this->incidenteDe($this->crearUsuario(), ['fecha_vencimiento' => now()]);
        $token = $this->iniciarSesion($this->usuarioConPermisos(ClavePermiso::VerDashboard, ClavePermiso::VerTodos));

        $this->dashboard($token)->assertJsonPath('data.vencidos', 0);
    }

    public function test_no_cuenta_incidentes_borrados(): void
    {
        $creador = $this->crearUsuario();
        $this->incidenteDe($creador);
        $this->incidenteDe($creador, ['deleted_at' => now(), 'fecha_vencimiento' => now()->subDay()]);
        $token = $this->iniciarSesion($this->usuarioConPermisos(ClavePermiso::VerDashboard, ClavePermiso::VerTodos));

        $this->dashboard($token)->assertJsonPath('data.total', 1)->assertJsonPath('data.vencidos', 0);
    }

    public function test_quien_ve_solo_lo_propio_cuenta_solo_lo_suyo(): void
    {
        $solicitante = $this->usuarioConPermisos(ClavePermiso::VerDashboard, ClavePermiso::VerPropios);
        $this->incidenteDe($solicitante, ['fecha_vencimiento' => now()->subDay()]);
        $this->incidenteDe($this->crearUsuario());
        $this->incidenteDe($this->crearUsuario());

        $this->dashboard($this->iniciarSesion($solicitante))
            ->assertJsonPath('data.total', 1)
            ->assertJsonPath('data.vencidos', 1);
    }

    public function test_quien_no_ve_ningun_incidente_recibe_ceros(): void
    {
        $this->incidenteDe($this->crearUsuario());
        $token = $this->iniciarSesion($this->usuarioConPermisos(ClavePermiso::VerDashboard));

        $this->dashboard($token)->assertOk()
            ->assertJsonPath('data.total', 0)
            ->assertJsonPath('data.vencidos', 0)
            ->assertJsonPath('data.por_estado.abierto', 0);
    }

    public function test_exige_el_permiso_del_dashboard_y_token(): void
    {
        $this->dashboard($this->iniciarSesion($this->usuarioConPermisos(ClavePermiso::VerTodos)))->assertForbidden();
        $this->sinCredencial()->getJson('/api/v1/dashboard')->assertUnauthorized();
    }

    public function test_usa_dos_consultas_de_conteo_sin_importar_el_volumen(): void
    {
        $token = $this->iniciarSesion($this->usuarioConPermisos(ClavePermiso::VerDashboard, ClavePermiso::VerTodos));
        $this->dashboard($token);

        $contar = function () use ($token): int {
            DB::flushQueryLog();
            DB::enableQueryLog();
            $this->dashboard($token)->assertOk();

            return collect(DB::getQueryLog())->filter(fn (array $q) => str_starts_with($q['query'], 'select') && str_contains($q['query'], 'count(*)'))->count();
        };

        $antes = $contar();
        $creador = $this->crearUsuario();
        foreach (range(1, 20) as $i) {
            $this->incidenteDe($creador);
        }

        $this->assertSame(2, $antes);
        $this->assertSame($antes, $contar());
    }
}
