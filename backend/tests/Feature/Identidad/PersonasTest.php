<?php

namespace Tests\Feature\Identidad;

use App\Identidad\Consultas\BuscarPersonas;
use App\Identidad\Dominio\ClavePermiso;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

// GET /personas: lo mínimo para elegir a alguien en un selector (asignar o filtrar), G-05.
class PersonasTest extends TestCase
{
    private string $token;

    protected function setUp(): void
    {
        parent::setUp();

        $this->prepararDatosBase();
        $this->token = $this->iniciarSesion($this->usuarioConPermisos(ClavePermiso::Reasignar));
    }

    private function personas(string $consulta = '', ?string $token = null): TestResponse
    {
        return $this->conToken($token ?? $this->token)->getJson('/api/v1/personas'.($consulta !== '' ? '?'.$consulta : ''));
    }

    /**
     * @return list<string>
     */
    private function nombres(string $consulta): array
    {
        return collect($this->personas($consulta)->assertOk()->json('data'))->pluck('name')->all();
    }

    public function test_devuelve_solo_id_nombre_y_si_esta_activa(): void
    {
        $persona = $this->crearUsuario('Solicitante', ['name' => 'Zoe Navarro']);

        $this->personas('q=Zoe')->assertOk()->assertExactJson(['data' => [
            ['id' => $persona->id, 'name' => 'Zoe Navarro', 'activo' => true],
        ]]);
    }

    public function test_para_asignar_solo_devuelve_quienes_atienden_incidentes_con_su_rol(): void
    {
        $this->crearUsuario('Solicitante', ['name' => 'Zoe Navarro']);
        $soporte = $this->crearUsuario('Soporte N2', ['name' => 'Zoe Pardo']);
        $administrador = $this->crearUsuario('Administrador', ['name' => 'Zoe Ríos']);

        $this->personas('q=Zoe&para_asignar=1')->assertOk()->assertExactJson(['data' => [
            ['id' => $soporte->id, 'name' => 'Zoe Pardo', 'activo' => true, 'rol' => 'Soporte N2'],
            ['id' => $administrador->id, 'name' => 'Zoe Ríos', 'activo' => true, 'rol' => 'Administrador'],
        ]]);
    }

    public function test_busca_por_nombre_sin_distinguir_acentos_y_ordena_por_nombre(): void
    {
        $this->crearUsuario('Solicitante', ['name' => 'Óscar Ramírez']);
        $this->crearUsuario('Solicitante', ['name' => 'Andrea Ramirez']);
        $this->crearUsuario('Solicitante', ['name' => 'Luis Pardo']);

        $this->assertSame(['Andrea Ramirez', 'Óscar Ramírez'], $this->nombres('q=ramirez'));
    }

    public function test_no_busca_por_correo(): void
    {
        $this->crearUsuario('Solicitante', ['name' => 'Ana Torres', 'email' => 'secreta@empresa-ejemplo.com']);

        $this->assertSame([], $this->nombres('q=secreta'));
    }

    public function test_por_defecto_excluye_a_las_inactivas_y_se_pueden_incluir(): void
    {
        $this->crearUsuario('Solicitante', ['name' => 'Zuleima Activa']);
        $this->crearUsuario('Solicitante', ['name' => 'Zuleima Inactiva', 'activo' => false]);

        $this->assertSame(['Zuleima Activa'], $this->nombres('q=Zuleima'));
        $this->assertSame(['Zuleima Activa', 'Zuleima Inactiva'], $this->nombres('q=Zuleima&incluir_inactivas=1'));
    }

    public function test_resuelve_personas_concretas_aunque_esten_inactivas(): void
    {
        $inactiva = $this->crearUsuario('Solicitante', ['name' => 'Pedro Gil', 'activo' => false]);
        $activa = $this->crearUsuario('Solicitante', ['name' => 'Carla Díaz']);

        $this->assertSame(['Carla Díaz', 'Pedro Gil'], $this->nombres("ids[]={$inactiva->id}&ids[]={$activa->id}&q=nadie"));
    }

    public function test_devuelve_como_mucho_el_maximo(): void
    {
        foreach (range(1, BuscarPersonas::MAXIMO + 5) as $n) {
            $this->crearUsuario('Solicitante', ['name' => sprintf('Persona %02d', $n)]);
        }

        $this->assertCount(BuscarPersonas::MAXIMO, $this->nombres('q=Persona'));
    }

    public function test_valida_los_parametros(): void
    {
        $this->personas('q='.str_repeat('a', 101))->assertUnprocessable()->assertJsonValidationErrors(['q'], 'errors');
        $this->personas('ids[]=abc')->assertUnprocessable()->assertJsonValidationErrors(['ids.0'], 'errors');
        $this->personas(implode('&', array_map(fn (int $n) => "ids[]={$n}", range(1, BuscarPersonas::MAXIMO + 1))))
            ->assertUnprocessable()->assertJsonValidationErrors(['ids'], 'errors');
    }

    public function test_la_pueden_usar_quien_asigna_ve_todo_audita_o_administra_usuarios(): void
    {
        foreach ([ClavePermiso::VerTodos, ClavePermiso::VerAuditoria, ClavePermiso::AdministrarUsuarios] as $permiso) {
            $this->personas('', $this->iniciarSesion($this->usuarioConPermisos($permiso)))->assertOk();
        }
    }

    public function test_quien_solo_ve_lo_propio_no_puede_listar_personas_y_sin_token_responde_401(): void
    {
        $this->personas('', $this->iniciarSesion($this->usuarioConPermisos(ClavePermiso::VerPropios, ClavePermiso::CrearIncidente)))->assertForbidden();
        $this->sinCredencial()->getJson('/api/v1/personas')->assertUnauthorized();
    }
}
