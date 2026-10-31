<?php

namespace Tests\Feature\Incidentes;

use App\Identidad\Dominio\ClavePermiso;
use App\Incidentes\Dominio\Categoria;
use App\Incidentes\Dominio\Estado;
use Tests\TestCase;

class AvisosDeIncidenteMasivoTest extends TestCase
{
    private string $token;

    protected function setUp(): void
    {
        parent::setUp();

        $this->prepararDatosBase();
        $this->freezeTime();
        $this->token = $this->iniciarSesion($this->usuarioConPermisos(ClavePermiso::VerTodos));
    }

    /**
     * Crea `$cantidad` incidentes repartidos entre `$personas` personas nuevas.
     *
     * @param  array<string, mixed>  $atributos
     */
    private function reportar(int $cantidad, int $personas, array $atributos = []): void
    {
        $creadores = collect(range(1, $personas))->map(fn () => $this->crearUsuario());

        for ($i = 0; $i < $cantidad; $i++) {
            $this->incidenteDe($creadores[$i % $personas], $atributos + [
                'categoria' => Categoria::Red,
                'titulo' => 'Sin internet en el piso '.$i,
                'descripcion' => 'No hay conexión de internet',
                'created_at' => now()->subMinutes(30),
            ]);
        }
    }

    public function test_avisa_con_las_palabras_que_se_repiten_y_sin_datos_de_personas(): void
    {
        $this->reportar(5, 3);

        $respuesta = $this->conToken($this->token)->getJson('/api/v1/avisos')->assertOk();

        $respuesta->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.categoria', 'red')
            ->assertJsonPath('data.0.incidentes', 5)
            ->assertJsonPath('data.0.personas', 3)
            ->assertJsonPath('data.0.desde', now()->subHours(2)->format('Y-m-d\TH:i:s\Z'));
        $this->assertContains('internet', $respuesta->json('data.0.palabras'));
        $this->assertSame(['categoria', 'incidentes', 'personas', 'palabras', 'desde'], array_keys($respuesta->json('data.0')));
    }

    public function test_no_avisa_con_menos_incidentes_o_menos_personas_que_el_umbral(): void
    {
        $this->reportar(4, 4);
        $this->conToken($this->token)->getJson('/api/v1/avisos')->assertOk()->assertJsonCount(0, 'data');

        $this->reportar(5, 2, ['categoria' => Categoria::Correo]);
        $this->conToken($this->token)->getJson('/api/v1/avisos')->assertOk()->assertJsonCount(0, 'data');
    }

    public function test_ignora_incidentes_fuera_de_la_ventana_resueltos_y_borrados(): void
    {
        $this->reportar(3, 3);
        $this->reportar(1, 1, ['created_at' => now()->subHours(3)]);
        $this->reportar(1, 1, ['estado' => Estado::Resuelto, 'solucion' => 'Se reinició el router.']);
        $this->reportar(1, 1, ['deleted_at' => now()]);

        $this->conToken($this->token)->getJson('/api/v1/avisos')->assertOk()->assertJsonCount(0, 'data');
    }

    public function test_los_umbrales_se_configuran(): void
    {
        config(['incidentes.avisos.minimo_incidentes' => 2, 'incidentes.avisos.minimo_personas' => 2]);
        $this->reportar(2, 2);

        $this->conToken($this->token)->getJson('/api/v1/avisos')->assertJsonCount(1, 'data');
    }

    public function test_solo_lo_ve_quien_ve_todos_los_incidentes(): void
    {
        $solicitante = $this->iniciarSesion($this->crearUsuario());

        $this->conToken($solicitante)->getJson('/api/v1/avisos')->assertForbidden();
        $this->sinCredencial()->getJson('/api/v1/avisos')->assertUnauthorized();
    }
}
