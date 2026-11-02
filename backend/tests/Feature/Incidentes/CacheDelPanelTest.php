<?php

namespace Tests\Feature\Incidentes;

use App\Identidad\Dominio\ClavePermiso;
use App\Identidad\Models\User;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CacheDelPanelTest extends TestCase
{
    private User $soporte;

    protected function setUp(): void
    {
        parent::setUp();

        $this->prepararDatosBase();
        $this->soporte = $this->usuarioConPermisos(ClavePermiso::VerDashboard, ClavePermiso::VerTodos, ClavePermiso::CrearIncidente);
        $this->incidenteDe($this->crearUsuario());
    }

    private function consultasAlPedir(string $ruta, string $token): int
    {
        DB::flushQueryLog();
        DB::enableQueryLog();
        $this->conToken($token)->getJson($ruta)->assertOk();
        $consultas = collect(DB::getQueryLog())->filter(fn (array $c) => str_contains($c['query'], 'from `incidentes`'))->count();
        DB::disableQueryLog();

        return $consultas;
    }

    public function test_la_segunda_vez_el_panel_sale_de_la_cache_sin_consultar_los_incidentes(): void
    {
        $token = $this->iniciarSesion($this->soporte);

        foreach (['/api/v1/dashboard', '/api/v1/dashboard/indicadores'] as $ruta) {
            $this->assertGreaterThan(0, $this->consultasAlPedir($ruta, $token));
            $this->assertSame(0, $this->consultasAlPedir($ruta, $token), "{$ruta} no salió de la caché.");
        }
    }

    public function test_un_incidente_que_cambia_renueva_el_panel(): void
    {
        $token = $this->iniciarSesion($this->soporte);
        $this->conToken($token)->getJson('/api/v1/dashboard')->assertJsonPath('data.total', 1);

        $this->conToken($token)->postJson('/api/v1/incidentes', ['titulo' => 'Sin internet', 'descripcion' => 'No hay conexión.'])->assertCreated();

        $this->conToken($token)->getJson('/api/v1/dashboard')->assertJsonPath('data.total', 2);
    }

    public function test_quien_ve_solo_lo_suyo_no_recibe_lo_guardado_para_quien_ve_todo(): void
    {
        $this->conToken($this->iniciarSesion($this->soporte))->getJson('/api/v1/dashboard')->assertJsonPath('data.total', 1);

        $solicitante = $this->usuarioConPermisos(ClavePermiso::VerDashboard, ClavePermiso::VerPropios);
        $this->conToken($this->iniciarSesion($solicitante))->getJson('/api/v1/dashboard')->assertJsonPath('data.total', 0);
    }
}
