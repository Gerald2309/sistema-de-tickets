<?php

namespace Tests\Feature\Incidentes;

use App\Identidad\Dominio\ClavePermiso;
use App\Identidad\Models\User;
use App\Incidentes\Dominio\Estado;
use Tests\TestCase;

// Filtros por creador, atajos «mis incidentes» y «asignados a mí», y «por vencer».
class FiltrosPersonalesDelListadoTest extends TestCase
{
    private User $soporte;

    private string $token;

    protected function setUp(): void
    {
        parent::setUp();

        $this->prepararDatosBase();
        $this->freezeTime();
        $this->soporte = $this->usuarioConPermisos(ClavePermiso::VerTodos, ClavePermiso::CrearIncidente);
        $this->token = $this->iniciarSesion($this->soporte);
    }

    /**
     * @return list<int>
     */
    private function ids(string $consulta): array
    {
        return collect($this->conToken($this->token)->getJson("/api/v1/incidentes?{$consulta}")->assertOk()->json('data'))
            ->pluck('id')->sort()->values()->all();
    }

    public function test_filtra_por_creador_y_por_el_atajo_mios(): void
    {
        $otro = $this->crearUsuario();
        $mio = $this->incidenteDe($this->soporte);
        $ajeno = $this->incidenteDe($otro);

        $this->assertSame([$ajeno->id], $this->ids('usuario_creador_id='.$otro->id));
        $this->assertSame([$mio->id], $this->ids('mios=1'));
        $this->assertEqualsCanonicalizing([$mio->id, $ajeno->id], $this->ids('mios=0'));
    }

    public function test_el_atajo_asignados_a_mi(): void
    {
        $creador = $this->crearUsuario();
        $mio = $this->incidenteDe($creador, ['usuario_asignado_id' => $this->soporte->id]);
        $this->incidenteDe($creador, ['usuario_asignado_id' => $this->crearUsuario()->id]);
        $this->incidenteDe($creador);

        $this->assertSame([$mio->id], $this->ids('asignados_a_mi=1'));
    }

    public function test_los_atajos_no_se_combinan_con_el_filtro_por_persona(): void
    {
        $this->conToken($this->token)->getJson('/api/v1/incidentes?mios=1&usuario_creador_id=5')
            ->assertUnprocessable()->assertJsonValidationErrors(['mios'], 'errors');
        $this->conToken($this->token)->getJson('/api/v1/incidentes?asignados_a_mi=1&usuario_asignado_id=5')
            ->assertUnprocessable()->assertJsonValidationErrors(['asignados_a_mi'], 'errors');
        $this->conToken($this->token)->getJson('/api/v1/incidentes?usuario_creador_id=999999')->assertUnprocessable();
    }

    public function test_quien_ve_solo_lo_propio_no_ve_mas_con_estos_filtros(): void
    {
        $solicitante = $this->usuarioConPermisos(ClavePermiso::VerPropios);
        $otro = $this->crearUsuario();
        $propio = $this->incidenteDe($solicitante);
        $this->incidenteDe($otro);
        $token = $this->iniciarSesion($solicitante);

        $respuesta = $this->conToken($token)->getJson('/api/v1/incidentes?usuario_creador_id='.$otro->id)->assertOk();
        $this->assertSame([], $respuesta->json('data'));
        $this->assertSame([$propio->id], collect($this->conToken($token)->getJson('/api/v1/incidentes?mios=1')->json('data'))->pluck('id')->all());
    }

    public function test_por_vencer_trae_los_pendientes_que_vencen_dentro_del_plazo(): void
    {
        $creador = $this->crearUsuario();
        $enDosHoras = $this->incidenteDe($creador, ['fecha_vencimiento' => now()->addHours(2)]);
        $enSeisHoras = $this->incidenteDe($creador, ['fecha_vencimiento' => now()->addHours(6), 'estado' => Estado::EnProgreso]);
        $this->incidenteDe($creador, ['fecha_vencimiento' => now()->addHours(30)]);
        $this->incidenteDe($creador, ['fecha_vencimiento' => now()->subHour()]);
        $this->incidenteDe($creador, ['fecha_vencimiento' => now()->addHour(), 'estado' => Estado::Resuelto, 'solucion' => 'Listo.']);
        $this->incidenteDe($creador, ['fecha_vencimiento' => now()->addHour(), 'deleted_at' => now()]);

        $this->assertSame([$enDosHoras->id], $this->ids('vence_en_horas=3'));
        $this->assertSame([$enDosHoras->id, $enSeisHoras->id], $this->ids('vence_en_horas=6'));
    }

    public function test_por_vencer_incluye_el_limite_y_excluye_lo_que_vence_ahora_mismo_hacia_atras(): void
    {
        $creador = $this->crearUsuario();
        $enElLimite = $this->incidenteDe($creador, ['fecha_vencimiento' => now()->addHours(4)]);
        $ahora = $this->incidenteDe($creador, ['fecha_vencimiento' => now()]);

        $this->assertEqualsCanonicalizing([$enElLimite->id, $ahora->id], $this->ids('vence_en_horas=4'));
        $this->assertSame([], $this->ids('vence_en_horas=4&estado=cerrado'));
    }

    public function test_el_plazo_debe_estar_entre_una_hora_y_treinta_dias(): void
    {
        foreach (['0', '721', 'abc'] as $horas) {
            $this->conToken($this->token)->getJson("/api/v1/incidentes?vence_en_horas={$horas}")
                ->assertUnprocessable()->assertJsonValidationErrors(['vence_en_horas'], 'errors');
        }
        $this->conToken($this->token)->getJson('/api/v1/incidentes?vence_en_horas=720')->assertOk();
    }
}
