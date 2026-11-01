<?php

namespace Tests\Feature\Incidentes;

use App\Identidad\Dominio\ClavePermiso;
use App\Identidad\Models\Cliente;
use App\Identidad\Models\User;
use App\Incidentes\Dominio\Estado;
use App\Incidentes\Dominio\PlazosDeServicio;
use App\Incidentes\Models\AcuerdoDeServicio;
use App\Incidentes\Models\Incidente;
use Illuminate\Support\Carbon;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class AcuerdosDeServicioTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->prepararDatosBase();
        // Lunes a las 10:00 en Bogotá.
        $this->travelTo(Carbon::parse('2026-11-02 15:00:00', 'UTC'));
    }

    private function ruta(Cliente $empresa): string
    {
        return "/api/v1/clientes/{$empresa->id}/acuerdo-de-servicio";
    }

    private function administrador(): User
    {
        return $this->usuarioConPermisos(ClavePermiso::AdministrarUsuarios);
    }

    /**
     * @param  array<string, mixed>  $cuerpo
     */
    private function guardar(Cliente $empresa, array $cuerpo): TestResponse
    {
        return $this->conToken($this->iniciarSesion($this->administrador()))->putJson($this->ruta($empresa), $cuerpo);
    }

    private function solicitanteDe(Cliente $empresa): User
    {
        return $this->crearUsuario('Solicitante', ['cliente_id' => $empresa->id]);
    }

    /**
     * @param  array<string, mixed>  $datos
     */
    private function crearIncidente(User $quien, array $datos = []): Incidente
    {
        $respuesta = $this->conToken($this->iniciarSesion($quien))
            ->postJson('/api/v1/incidentes', $datos + ['titulo' => 'No imprime', 'descripcion' => 'La impresora no responde.'])
            ->assertCreated();

        return Incidente::findOrFail($respuesta->json('data.id'));
    }

    public function test_sin_acuerdo_propio_una_empresa_ve_los_plazos_predeterminados(): void
    {
        $this->conToken($this->iniciarSesion($this->administrador()))
            ->getJson($this->ruta(Cliente::factory()->create()))
            ->assertOk()
            ->assertJsonPath('data.horario_laboral', false)
            ->assertJsonPath('data.plazos.critica', ['respuesta_minutos' => 15, 'resolucion_minutos' => 120]);
    }

    public function test_guardar_dos_veces_reemplaza_el_acuerdo_de_la_empresa(): void
    {
        $empresa = Cliente::factory()->create();
        $plazos = PlazosDeServicio::PREDETERMINADOS;

        $this->guardar($empresa, ['horario_laboral' => true, 'plazos' => $plazos])->assertOk();
        $plazos['alta'] = ['respuesta_minutos' => 30, 'resolucion_minutos' => 90];
        $this->guardar($empresa, ['horario_laboral' => false, 'plazos' => $plazos])
            ->assertOk()
            ->assertJsonPath('data.horario_laboral', false)
            ->assertJsonPath('data.plazos.alta.resolucion_minutos', 90);

        $this->assertSame(1, AcuerdoDeServicio::where('cliente_id', $empresa->id)->count());
    }

    public function test_exige_todos_los_plazos_y_que_la_respuesta_no_tarde_mas_que_la_resolucion(): void
    {
        $empresa = Cliente::factory()->create();
        $plazos = PlazosDeServicio::PREDETERMINADOS;

        $sinUno = $plazos;
        unset($sinUno['baja']);
        $this->guardar($empresa, ['horario_laboral' => false, 'plazos' => $sinUno])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('plazos.baja.resolucion_minutos');

        $plazos['media'] = ['respuesta_minutos' => 500, 'resolucion_minutos' => 100];
        $this->guardar($empresa, ['horario_laboral' => false, 'plazos' => $plazos])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('plazos.media.respuesta_minutos');

        $this->assertSame(0, AcuerdoDeServicio::count());
    }

    public function test_solo_quien_administra_usuarios_ve_o_cambia_un_acuerdo(): void
    {
        $empresa = Cliente::factory()->create();
        $token = $this->iniciarSesion($this->crearUsuario('Soporte N1'));

        $this->conToken($token)->getJson($this->ruta($empresa))->assertForbidden();
        $this->conToken($token)
            ->putJson($this->ruta($empresa), ['horario_laboral' => true, 'plazos' => PlazosDeServicio::PREDETERMINADOS])
            ->assertForbidden();
    }

    public function test_un_incidente_nuevo_toma_los_limites_del_acuerdo_de_su_empresa(): void
    {
        $empresa = Cliente::factory()->create();
        $plazos = PlazosDeServicio::PREDETERMINADOS;
        $plazos['alta'] = ['respuesta_minutos' => 30, 'resolucion_minutos' => 90];
        AcuerdoDeServicio::create(['cliente_id' => $empresa->id, 'horario_laboral' => false, 'plazos' => $plazos]);

        $incidente = $this->crearIncidente($this->solicitanteDe($empresa), ['prioridad' => 'alta']);

        $this->assertEquals(now()->addMinutes(30), $incidente->vence_respuesta_en);
        $this->assertEquals(now()->addMinutes(90), $incidente->fecha_vencimiento);
    }

    public function test_en_horario_laboral_el_plazo_salta_lo_que_queda_fuera_de_la_jornada(): void
    {
        // Viernes a las 17:00 en Bogotá.
        $this->travelTo(Carbon::parse('2026-11-06 22:00:00', 'UTC'));
        $empresa = Cliente::factory()->create();
        AcuerdoDeServicio::create(['cliente_id' => $empresa->id, 'horario_laboral' => true, 'plazos' => PlazosDeServicio::PREDETERMINADOS]);

        $incidente = $this->crearIncidente($this->solicitanteDe($empresa), ['prioridad' => 'critica']);

        // Queda una hora el viernes y otra el lunes desde las 8:00 de Bogotá (13:00 UTC).
        $this->assertSame('2026-11-09 14:00:00', $incidente->fecha_vencimiento->copy()->utc()->format('Y-m-d H:i:s'));
    }

    public function test_sin_empresa_ni_prioridad_rigen_los_predeterminados_y_una_fecha_escrita_a_mano_gana(): void
    {
        $interno = $this->crearUsuario('Soporte N1');

        $calculado = $this->crearIncidente($interno);
        $this->assertEquals(now()->addMinutes(240), $calculado->vence_respuesta_en);
        $this->assertEquals(now()->addMinutes(1440), $calculado->fecha_vencimiento);

        $aMano = $this->crearIncidente($interno, ['fecha_vencimiento' => '2026-12-01T10:00:00Z']);
        $this->assertSame('2026-12-01 10:00:00', $aMano->fecha_vencimiento->copy()->utc()->format('Y-m-d H:i:s'));
        $this->assertEquals(now()->addMinutes(240), $aMano->vence_respuesta_en);
    }

    public function test_propone_los_limites_para_la_empresa_de_quien_pregunta(): void
    {
        $empresa = Cliente::factory()->create();
        $plazos = PlazosDeServicio::PREDETERMINADOS;
        $plazos['baja'] = ['respuesta_minutos' => 10, 'resolucion_minutos' => 20];
        AcuerdoDeServicio::create(['cliente_id' => $empresa->id, 'horario_laboral' => false, 'plazos' => $plazos]);

        $this->conToken($this->iniciarSesion($this->solicitanteDe($empresa)))
            ->getJson('/api/v1/plazo-propuesto?prioridad=baja')
            ->assertOk()
            ->assertExactJson(['data' => [
                'vence_respuesta_en' => '2026-11-02T15:10:00Z',
                'fecha_vencimiento' => '2026-11-02T15:20:00Z',
            ]]);
    }

    public function test_la_primera_respuesta_la_cumple_un_mensaje_publico_de_otra_persona(): void
    {
        $solicitante = $this->crearUsuario();
        $soporte = $this->crearUsuario('Soporte N1');
        $incidente = $this->incidenteDe($solicitante, ['usuario_asignado_id' => $soporte->id]);
        $ruta = "/api/v1/incidentes/{$incidente->id}/mensajes";

        $this->conToken($this->iniciarSesion($solicitante))->postJson($ruta, ['cuerpo' => 'Sigue sin imprimir.'])->assertCreated();
        $this->assertNull($incidente->refresh()->primera_respuesta_en, 'Su propio mensaje no es una respuesta.');

        $tokenDeSoporte = $this->iniciarSesion($soporte);
        $this->conToken($tokenDeSoporte)->postJson($ruta, ['cuerpo' => 'Nota para el equipo.', 'interno' => true])->assertCreated();
        $this->assertNull($incidente->refresh()->primera_respuesta_en, 'Una nota interna no le llega al solicitante.');

        $this->conToken($tokenDeSoporte)->postJson($ruta, ['cuerpo' => 'Ya lo reviso.'])->assertCreated();
        $momento = $incidente->refresh()->primera_respuesta_en;
        $this->assertEquals(now(), $momento);

        $this->travel(5)->minutes();
        $this->conToken($tokenDeSoporte)->postJson($ruta, ['cuerpo' => 'Sigo en ello.'])->assertCreated();
        $this->assertEquals($momento, $incidente->refresh()->primera_respuesta_en, 'Solo cuenta la primera.');
    }

    public function test_resolver_sin_haber_escrito_cumple_la_primera_respuesta(): void
    {
        $soporte = $this->crearUsuario('Soporte N1');
        $incidente = $this->incidenteAtendidoPor($soporte, ['estado' => Estado::EnProgreso]);

        $this->conToken($this->iniciarSesion($soporte))
            ->patchJson("/api/v1/incidentes/{$incidente->id}", ['estado' => 'resuelto', 'solucion' => 'Listo.'])
            ->assertOk()
            ->assertJsonPath('data.primera_respuesta_en', now()->toIso8601ZuluString());
    }
}
