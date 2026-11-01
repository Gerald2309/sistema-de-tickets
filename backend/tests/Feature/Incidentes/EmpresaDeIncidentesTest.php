<?php

namespace Tests\Feature\Incidentes;

use App\Identidad\Dominio\ClavePermiso;
use App\Identidad\Models\Cliente;
use App\Identidad\Models\User;
use Tests\TestCase;

// Empresa de un incidente (F-10): la del solicitante en el momento de reportar, que no cambia después.
class EmpresaDeIncidentesTest extends TestCase
{
    private Cliente $empresa;

    private User $solicitante;

    protected function setUp(): void
    {
        parent::setUp();

        $this->prepararDatosBase();
        $this->empresa = Cliente::factory()->create(['nombre' => 'Constructora Andina']);
        $this->solicitante = $this->usuarioConPermisos(ClavePermiso::CrearIncidente, ClavePermiso::VerPropios);
        $this->solicitante->update(['cliente_id' => $this->empresa->id]);
    }

    /**
     * @return array<string, mixed>
     */
    private function datosDeIncidente(): array
    {
        return [
            'titulo' => 'Sin internet en el piso 3',
            'descripcion' => 'No hay conexión desde esta mañana.',
        ];
    }

    public function test_el_incidente_guarda_la_empresa_del_solicitante_y_no_la_toma_de_la_peticion(): void
    {
        $otra = Cliente::factory()->create();

        $this->conToken($this->iniciarSesion($this->solicitante))
            ->postJson('/api/v1/incidentes', $this->datosDeIncidente() + ['cliente_id' => $otra->id])
            ->assertCreated()
            ->assertJsonPath('data.cliente', ['id' => $this->empresa->id, 'nombre' => 'Constructora Andina']);
    }

    public function test_si_la_persona_cambia_de_empresa_sus_incidentes_anteriores_se_quedan_en_la_anterior(): void
    {
        $token = $this->iniciarSesion($this->solicitante);
        $id = $this->conToken($token)->postJson('/api/v1/incidentes', $this->datosDeIncidente())->assertCreated()->json('data.id');

        $nueva = Cliente::factory()->create(['nombre' => 'Banco Aliado']);
        $this->solicitante->update(['cliente_id' => $nueva->id]);

        $this->conToken($token)->getJson("/api/v1/incidentes/{$id}")->assertJsonPath('data.cliente.nombre', 'Constructora Andina');
        $this->conToken($token)->postJson('/api/v1/incidentes', $this->datosDeIncidente())->assertJsonPath('data.cliente.nombre', 'Banco Aliado');
    }

    public function test_el_personal_de_psicol_reporta_sin_empresa(): void
    {
        $soporte = $this->usuarioConPermisos(ClavePermiso::CrearIncidente, ClavePermiso::VerTodos);

        $this->conToken($this->iniciarSesion($soporte))->postJson('/api/v1/incidentes', $this->datosDeIncidente())
            ->assertCreated()->assertJsonPath('data.cliente', null);
    }

    public function test_el_listado_filtra_por_empresa_y_valida_el_filtro(): void
    {
        $deLaEmpresa = $this->incidenteDe($this->solicitante, ['cliente_id' => $this->empresa->id]);
        $this->incidenteDe($this->crearUsuario());
        $token = $this->iniciarSesion($this->usuarioConPermisos(ClavePermiso::VerTodos));

        $respuesta = $this->conToken($token)->getJson("/api/v1/incidentes?cliente_id={$this->empresa->id}")->assertOk();
        $this->assertSame([$deLaEmpresa->id], array_column($respuesta->json('data'), 'id'));

        $this->conToken($token)->getJson('/api/v1/incidentes?cliente_id=999999')
            ->assertUnprocessable()->assertJsonValidationErrors(['cliente_id'], 'errors');
    }

    public function test_el_filtro_por_empresa_no_amplia_lo_que_ve_un_solicitante(): void
    {
        $companera = $this->crearUsuario('Solicitante', ['cliente_id' => $this->empresa->id]);
        $propio = $this->incidenteDe($this->solicitante, ['cliente_id' => $this->empresa->id]);
        $this->incidenteDe($companera, ['cliente_id' => $this->empresa->id]);

        $respuesta = $this->conToken($this->iniciarSesion($this->solicitante))->getJson("/api/v1/incidentes?cliente_id={$this->empresa->id}")->assertOk();
        $this->assertSame([$propio->id], array_column($respuesta->json('data'), 'id'));
    }
}
