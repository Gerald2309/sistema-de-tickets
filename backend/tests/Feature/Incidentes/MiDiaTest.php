<?php

namespace Tests\Feature\Incidentes;

use App\Identidad\Models\User;
use App\Incidentes\Dominio\Estado;
use App\Incidentes\Dominio\Jornada;
use App\Incidentes\Dominio\Prioridad;
use App\Incidentes\Eventos\IncidentesCambiados;
use App\Incidentes\Models\EntradaDelPlan;
use App\Incidentes\Models\Incidente;
use App\Incidentes\Models\NotaPersonal;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class MiDiaTest extends TestCase
{
    private const HOY = '2030-01-15';

    private const AYER = '2030-01-14';

    private User $soporte;

    private string $token;

    protected function setUp(): void
    {
        parent::setUp();

        Event::fake([IncidentesCambiados::class]);
        $this->soporte = $this->crearUsuario('Soporte N1');
        $this->token = $this->iniciarSesion($this->soporte);
    }

    private function yo(): static
    {
        return $this->conToken($this->token);
    }

    /**
     * @param  array<string, mixed>  $atributos
     */
    private function asignado(array $atributos = []): Incidente
    {
        return $this->incidenteAtendidoPor($this->soporte, $atributos + ['estado' => Estado::EnProgreso]);
    }

    /**
     * @param  array<string, mixed>  $cuerpo
     */
    private function planear(array $cuerpo): TestResponse
    {
        return $this->yo()->postJson('/api/v1/mi-dia/plan', $cuerpo + ['fecha' => self::HOY]);
    }

    private function plan(string $desde = self::HOY, string $hasta = self::HOY): TestResponse
    {
        return $this->yo()->getJson("/api/v1/mi-dia/plan?desde={$desde}&hasta={$hasta}");
    }

    public function test_es_solo_para_quien_atiende_incidentes(): void
    {
        $solicitante = $this->iniciarSesion($this->crearUsuario());

        $this->conToken($solicitante)->getJson('/api/v1/mi-dia/plan?desde='.self::HOY.'&hasta='.self::HOY)->assertForbidden();
        $this->conToken($solicitante)->postJson('/api/v1/mi-dia/notas', ['texto' => 'Hola'])->assertForbidden();
        $this->plan()->assertOk()->assertJsonCount(0, 'data');
    }

    public function test_planear_pone_el_incidente_en_el_primer_hueco_desde_la_hora_pedida(): void
    {
        $primero = $this->asignado();
        $segundo = $this->asignado();

        // A las 10:20 el primer hueco es 10:30; una hora por defecto.
        $this->planear(['incidente_id' => $primero->id, 'inicio' => 600])
            ->assertCreated()
            ->assertJsonPath('data.inicio', 600)
            ->assertJsonPath('data.duracion', Jornada::DURACION_PREDETERMINADA)
            ->assertJsonPath('data.incidente.codigo', 'INC-'.str_pad((string) $primero->id, 6, '0', STR_PAD_LEFT));
        $this->planear(['incidente_id' => $segundo->id, 'inicio' => 630])->assertCreated()->assertJsonPath('data.inicio', 660);

        // Planearlo otra vez ese día lo mueve: no se duplica.
        $this->planear(['incidente_id' => $primero->id, 'inicio' => 840])->assertOk()->assertJsonPath('data.inicio', 840);
        $this->assertSame(2, EntradaDelPlan::count());
        $this->plan()->assertOk()->assertJsonPath('data.0.incidente.id', $segundo->id)->assertJsonPath('data.1.inicio', 840);
    }

    public function test_cambiar_la_duracion_es_exacto_y_moverla_busca_el_hueco(): void
    {
        $id = $this->planear(['incidente_id' => $this->asignado()->id, 'inicio' => 600])->json('data.id');
        $this->planear(['incidente_id' => $this->asignado()->id, 'inicio' => 660]);

        $this->yo()->patchJson("/api/v1/mi-dia/plan/{$id}", ['duracion' => 90])
            ->assertConflict()
            ->assertJsonPath('type', 'urn:incidentes:problema:no-cabe-en-el-dia');
        $this->yo()->patchJson("/api/v1/mi-dia/plan/{$id}", ['duracion' => 30])->assertOk()->assertJsonPath('data.inicio', 600);
        $this->yo()->patchJson("/api/v1/mi-dia/plan/{$id}", ['inicio' => 660])->assertOk()->assertJsonPath('data.inicio', 720);
        $this->yo()->patchJson("/api/v1/mi-dia/plan/{$id}", ['fecha' => '2030-01-16', 'inicio' => 600])
            ->assertOk()
            ->assertJsonPath('data.fecha', '2030-01-16');
    }

    public function test_la_hora_y_la_duracion_van_en_tramos_de_media_hora_dentro_de_la_jornada(): void
    {
        $incidente = $this->asignado();

        $this->planear(['incidente_id' => $incidente->id, 'inicio' => 610])->assertUnprocessable()->assertJsonValidationErrors('inicio');
        $this->planear(['incidente_id' => $incidente->id, 'inicio' => Jornada::FIN])->assertUnprocessable()->assertJsonValidationErrors('inicio');
        $this->planear(['incidente_id' => $incidente->id, 'inicio' => 600, 'duracion' => 240])->assertUnprocessable()->assertJsonValidationErrors('duracion');
        $this->planear(['inicio' => 600])->assertUnprocessable()->assertJsonValidationErrors(['incidente_id', 'nota_id']);
    }

    public function test_solo_se_planea_lo_que_se_ve_y_sigue_sin_resolver(): void
    {
        $resuelto = $this->asignado(['estado' => Estado::Resuelto, 'solucion' => 'Listo.']);

        $this->planear(['incidente_id' => $resuelto->id, 'inicio' => 600])->assertUnprocessable()->assertJsonValidationErrors('incidente_id');
        $this->planear(['incidente_id' => 999_999, 'inicio' => 600])->assertUnprocessable();
    }

    public function test_cada_persona_solo_alcanza_su_agenda(): void
    {
        $id = $this->planear(['incidente_id' => $this->asignado()->id, 'inicio' => 600])->json('data.id');
        $otra = $this->iniciarSesion($this->crearUsuario('Soporte N2'));

        $this->conToken($otra)->getJson('/api/v1/mi-dia/plan?desde='.self::HOY.'&hasta='.self::HOY)->assertOk()->assertJsonCount(0, 'data');
        $this->conToken($otra)->patchJson("/api/v1/mi-dia/plan/{$id}", ['duracion' => 30])->assertNotFound();
        $this->conToken($otra)->deleteJson("/api/v1/mi-dia/plan/{$id}")->assertNotFound();

        $this->yo()->deleteJson("/api/v1/mi-dia/plan/{$id}")->assertNoContent();
        $this->assertSame(0, EntradaDelPlan::count());
    }

    public function test_por_planear_trae_lo_asignado_sin_resolver_que_nunca_tuvo_dia_lo_que_vence_antes_primero(): void
    {
        $despues = $this->asignado(['fecha_vencimiento' => now()->addDays(5), 'titulo' => 'Proyector de la sala 2']);
        $antes = $this->asignado(['fecha_vencimiento' => now()->addHour(), 'titulo' => 'La VPN se desconecta']);
        $planeado = $this->asignado();
        $this->asignado(['estado' => Estado::Resuelto, 'solucion' => 'Listo.']);
        $this->incidenteAtendidoPor($this->crearUsuario('Soporte N2'));
        $this->planear(['incidente_id' => $planeado->id, 'inicio' => 600]);
        $this->yo()->putJson("/api/v1/mi-dia/bloqueos/{$antes->id}", ['motivo' => 'Esperando al proveedor'])->assertNoContent();

        $this->yo()->getJson('/api/v1/mi-dia/por-planear')->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.id', $antes->id)
            ->assertJsonPath('data.0.bloqueo', 'Esperando al proveedor')
            ->assertJsonPath('data.1.id', $despues->id)
            ->assertJsonPath('data.1.bloqueo', null);
        $this->yo()->getJson('/api/v1/mi-dia/por-planear?buscar=proyector')->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $despues->id);
        $this->yo()->getJson('/api/v1/mi-dia/por-planear?buscar=INC-'.$antes->id)->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $antes->id);
        $this->yo()->getJson('/api/v1/mi-dia/por-planear?buscar=%25')->assertOk()->assertJsonCount(0, 'data');
    }

    public function test_lo_que_quedo_de_un_dia_anterior_aparece_como_pendiente_hasta_volver_a_planearlo(): void
    {
        $pendiente = $this->asignado();
        $this->yo()->postJson('/api/v1/mi-dia/plan', ['fecha' => self::AYER, 'inicio' => 600, 'incidente_id' => $pendiente->id])->assertCreated();

        $this->yo()->getJson('/api/v1/mi-dia/pendientes?fecha='.self::HOY)->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $pendiente->id);
        $this->yo()->getJson('/api/v1/mi-dia/por-planear')->assertOk()->assertJsonCount(0, 'data');

        $this->planear(['incidente_id' => $pendiente->id, 'inicio' => 600])->assertCreated();
        $this->yo()->getJson('/api/v1/mi-dia/pendientes?fecha='.self::HOY)->assertOk()->assertJsonCount(0, 'data');
    }

    public function test_sugerir_llena_hasta_lo_habitual_con_lo_mas_urgente_sin_lo_bloqueado(): void
    {
        $bloqueado = $this->asignado(['fecha_vencimiento' => now()->subHour(), 'prioridad' => Prioridad::Critica]);
        $this->yo()->putJson("/api/v1/mi-dia/bloqueos/{$bloqueado->id}", ['motivo' => 'Tóner pedido a compras']);
        $urgentes = collect(range(1, Jornada::CAPACIDAD_HABITUAL + 2))
            ->map(fn (int $dias) => $this->asignado(['fecha_vencimiento' => now()->addDays($dias)]));

        $this->yo()->postJson('/api/v1/mi-dia/sugerencia', ['fecha' => self::HOY, 'desde' => 620])->assertOk()
            ->assertJsonCount(Jornada::CAPACIDAD_HABITUAL, 'data')
            ->assertJsonPath('data.0.incidente.id', $urgentes[0]->id)
            ->assertJsonPath('data.0.inicio', 630)
            ->assertJsonPath('data.1.inicio', 690);
        $this->assertFalse(EntradaDelPlan::where('incidente_id', $bloqueado->id)->exists());

        $this->yo()->postJson('/api/v1/mi-dia/sugerencia', ['fecha' => self::HOY, 'desde' => 620])->assertOk()->assertJsonCount(0, 'data');
    }

    public function test_las_notas_son_privadas_se_ligan_al_incidente_que_mencionan_y_se_les_da_hora(): void
    {
        $incidente = $this->asignado();
        $codigo = 'INC-'.str_pad((string) $incidente->id, 6, '0', STR_PAD_LEFT);

        $nota = $this->yo()->postJson('/api/v1/mi-dia/notas', ['texto' => "  Llamar a Pedro por {$codigo}  "])
            ->assertCreated()
            ->assertJsonPath('data.texto', "Llamar a Pedro por {$codigo}")
            ->assertJsonPath('data.incidente.codigo', $codigo)
            ->json('data');
        $this->yo()->postJson('/api/v1/mi-dia/notas', ['texto' => str_repeat('a', NotaPersonal::MAXIMO_TEXTO + 1)])->assertUnprocessable();

        $otra = $this->iniciarSesion($this->crearUsuario('Soporte N2'));
        $this->conToken($otra)->getJson('/api/v1/mi-dia/notas?fecha='.self::HOY)->assertOk()->assertJsonCount(0, 'data');
        $this->conToken($otra)->patchJson("/api/v1/mi-dia/notas/{$nota['id']}", ['hecha' => true])->assertNotFound();
        $this->conToken($otra)->postJson('/api/v1/mi-dia/plan', ['fecha' => self::HOY, 'inicio' => 600, 'nota_id' => $nota['id']])->assertUnprocessable();

        // Con hora hoy, sale de la pizarra y entra a la agenda.
        $this->planear(['nota_id' => $nota['id'], 'inicio' => 780])->assertCreated()->assertJsonPath('data.duracion', Jornada::DURACION_DE_NOTA);
        $this->yo()->getJson('/api/v1/mi-dia/notas?fecha='.self::HOY)->assertOk()->assertJsonCount(0, 'data');
        $this->plan()->assertJsonPath('data.0.nota.id', $nota['id']);

        $this->yo()->patchJson("/api/v1/mi-dia/notas/{$nota['id']}", ['hecha' => true])->assertOk()->assertJsonPath('data.hecha', true);
        $this->yo()->deleteJson("/api/v1/mi-dia/notas/{$nota['id']}")->assertNoContent();
        $this->assertSame(0, EntradaDelPlan::count(), 'Borrar la nota la quita de la agenda.');
    }

    public function test_la_marca_de_bloqueado_es_personal_y_no_toca_el_incidente(): void
    {
        $incidente = $this->asignado();
        $antes = $incidente->fresh()->updated_at;

        $this->yo()->putJson("/api/v1/mi-dia/bloqueos/{$incidente->id}", ['motivo' => ''])->assertUnprocessable();
        $this->yo()->putJson("/api/v1/mi-dia/bloqueos/{$incidente->id}", ['motivo' => 'Primero'])->assertNoContent();
        $this->yo()->putJson("/api/v1/mi-dia/bloqueos/{$incidente->id}", ['motivo' => 'Esperando al proveedor'])->assertNoContent();
        $this->assertSame('Esperando al proveedor', DB::table('bloqueos_personales')->value('motivo'));
        $this->assertEquals($antes, $incidente->fresh()->updated_at);

        $this->conToken($this->iniciarSesion($this->crearUsuario()))->putJson("/api/v1/mi-dia/bloqueos/{$incidente->id}", ['motivo' => 'x'])->assertNotFound();
        $this->yo()->deleteJson("/api/v1/mi-dia/bloqueos/{$incidente->id}")->assertNoContent();
        $this->assertSame(0, DB::table('bloqueos_personales')->count());
    }

    public function test_continuar_donde_quedo_es_lo_ultimo_que_toco(): void
    {
        $this->yo()->getJson('/api/v1/mi-dia/ultimo')->assertOk()->assertJsonPath('data', null);

        $incidente = $this->asignado();
        $this->yo()->postJson("/api/v1/incidentes/{$incidente->id}/mensajes", ['cuerpo' => '¿Ya reinició el equipo?'])->assertCreated();

        $this->yo()->getJson('/api/v1/mi-dia/ultimo')->assertOk()
            ->assertJsonPath('data.incidente.id', $incidente->id)
            ->assertJsonPath('data.accion', 'mensaje');
    }

    public function test_leer_la_agenda_no_hace_una_consulta_por_entrada(): void
    {
        $this->freezeTime();
        $this->plan()->assertOk();

        $consultas = function (int $cantidad): int {
            foreach (range(1, $cantidad) as $n) {
                $incidente = $this->asignado();
                DB::table('bloqueos_personales')->insert(['usuario_id' => $this->soporte->id, 'incidente_id' => $incidente->id, 'motivo' => 'x']);
                EntradaDelPlan::create(['usuario_id' => $this->soporte->id, 'fecha' => self::HOY, 'inicio' => Jornada::INICIO + ($n * 30) % 600, 'duracion' => 30, 'incidente_id' => $incidente->id]);
            }
            DB::flushQueryLog();
            DB::enableQueryLog();
            $this->plan()->assertOk();

            return count(DB::getQueryLog());
        };

        $this->assertSame($consultas(2), $consultas(8), 'Más entradas no debe significar más consultas (N+1).');
    }
}
