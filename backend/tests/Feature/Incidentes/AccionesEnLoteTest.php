<?php

namespace Tests\Feature\Incidentes;

use App\Identidad\Dominio\ClavePermiso;
use App\Identidad\Models\User;
use App\Incidentes\Dominio\Estado;
use App\Incidentes\Dominio\EventoHistorial;
use App\Incidentes\Dominio\Prioridad;
use App\Incidentes\Models\HistorialIncidente;
use App\Incidentes\Models\Incidente;
use Tests\TestCase;

class AccionesEnLoteTest extends TestCase
{
    private User $soporte;

    private User $creador;

    private string $token;

    protected function setUp(): void
    {
        parent::setUp();

        $this->prepararDatosBase();
        $this->creador = $this->crearUsuario();
        $this->soporte = $this->usuarioConPermisos(
            ClavePermiso::VerTodos,
            ClavePermiso::EditarTodos,
            ClavePermiso::CambiarEstado,
            ClavePermiso::Reasignar,
            ClavePermiso::Borrar,
        );
        $this->token = $this->iniciarSesion($this->soporte);
    }

    /**
     * Incidentes del mismo solicitante que atiende el soporte de la prueba, salvo que se indique otra cosa.
     *
     * @return list<Incidente>
     */
    private function crearIncidentes(int $cantidad, array $atributos = []): array
    {
        $atributos += ['usuario_asignado_id' => $this->soporte->id];

        return array_map(fn () => $this->incidenteDe($this->creador, $atributos), range(1, $cantidad));
    }

    /**
     * @param  list<Incidente>  $incidentes
     * @return list<int>
     */
    private function ids(array $incidentes): array
    {
        return array_map(fn (Incidente $incidente) => $incidente->id, $incidentes);
    }

    public function test_cambia_el_estado_de_varios_y_deja_historial_de_cada_uno(): void
    {
        $incidentes = $this->crearIncidentes(3);

        $respuesta = $this->conToken($this->token)->patchJson('/api/v1/incidentes/lote', [
            'ids' => $this->ids($incidentes),
            'cambios' => ['estado' => 'en_progreso'],
        ])->assertOk()->assertJsonCount(3, 'data');

        $this->assertSame($this->ids($incidentes), array_column($respuesta->json('data'), 'id'));
        foreach ($incidentes as $incidente) {
            $this->assertSame(Estado::EnProgreso, $incidente->fresh()->estado);
            $this->assertSame(1, HistorialIncidente::where('incidente_id', $incidente->id)->where('evento', EventoHistorial::Modificacion->value)->count());
        }
    }

    public function test_reasigna_y_cambia_la_prioridad_de_varios(): void
    {
        $incidentes = $this->crearIncidentes(2);
        $asignado = $this->crearUsuario('Soporte N1');

        $this->conToken($this->token)->patchJson('/api/v1/incidentes/lote', [
            'ids' => $this->ids($incidentes),
            'cambios' => ['usuario_asignado_id' => $asignado->id, 'prioridad' => 'critica'],
        ])->assertOk();

        foreach ($incidentes as $incidente) {
            $this->assertSame($asignado->id, $incidente->fresh()->usuario_asignado_id);
            $this->assertSame(Prioridad::Critica, $incidente->fresh()->prioridad);
        }
    }

    public function test_resolver_en_lote_exige_la_solucion_y_se_aplica_a_todos(): void
    {
        $incidentes = $this->crearIncidentes(2, ['estado' => Estado::EnProgreso]);

        $this->conToken($this->token)->patchJson('/api/v1/incidentes/lote', ['ids' => $this->ids($incidentes), 'cambios' => ['estado' => 'resuelto']])
            ->assertUnprocessable()->assertJsonValidationErrors(['cambios'], 'errors');

        $this->conToken($this->token)->patchJson('/api/v1/incidentes/lote', ['ids' => $this->ids($incidentes), 'cambios' => ['estado' => 'resuelto', 'solucion' => 'Se reinició el servicio.']])
            ->assertOk();
        foreach ($incidentes as $incidente) {
            $this->assertSame('Se reinició el servicio.', $incidente->fresh()->solucion);
        }
    }

    public function test_si_uno_no_puede_cambiar_no_se_modifica_ninguno_y_el_error_dice_cual(): void
    {
        [$a, $b] = $this->crearIncidentes(2);
        [$cerrado] = $this->crearIncidentes(1, ['estado' => Estado::Cerrado, 'solucion' => 'Listo.']);

        $respuesta = $this->conToken($this->token)->patchJson('/api/v1/incidentes/lote', [
            'ids' => [$a->id, $b->id, $cerrado->id],
            'cambios' => ['estado' => 'en_progreso'],
        ])->assertUnprocessable();

        $this->assertStringContainsString(sprintf('INC-%06d', $cerrado->id), $respuesta->json('errors.ids.0'));
        $this->assertSame(Estado::Abierto, $a->fresh()->estado);
        $this->assertSame(Estado::Abierto, $b->fresh()->estado);
        $this->assertSame(0, HistorialIncidente::where('evento', EventoHistorial::Modificacion->value)->count());
    }

    public function test_si_falta_un_incidente_se_rechaza_todo_como_no_encontrado(): void
    {
        [$a] = $this->crearIncidentes(1);

        $this->conToken($this->token)->patchJson('/api/v1/incidentes/lote', ['ids' => [$a->id, 999999], 'cambios' => ['estado' => 'en_progreso']])->assertNotFound();
        $this->conToken($this->token)->deleteJson('/api/v1/incidentes/lote', ['ids' => [$a->id, 999999]])->assertNotFound();

        $this->assertSame(Estado::Abierto, $a->fresh()->estado);
        $this->assertNull($a->fresh()->deleted_at);
    }

    public function test_un_incidente_ajeno_a_quien_solo_ve_lo_propio_responde_no_encontrado(): void
    {
        $solicitante = $this->usuarioConPermisos(ClavePermiso::VerPropios, ClavePermiso::EditarPropios);
        $propio = $this->incidenteDe($solicitante);
        $ajeno = $this->incidenteDe($this->creador);
        $token = $this->iniciarSesion($solicitante);

        $this->conToken($token)->patchJson('/api/v1/incidentes/lote', ['ids' => [$propio->id, $ajeno->id], 'cambios' => ['prioridad' => 'alta']])->assertNotFound();
        $this->conToken($token)->patchJson('/api/v1/incidentes/lote', ['ids' => [$propio->id], 'cambios' => ['prioridad' => 'alta']])->assertOk();
    }

    public function test_cada_campo_exige_su_permiso(): void
    {
        $agente = $this->usuarioConPermisos(ClavePermiso::VerTodos, ClavePermiso::CambiarEstado);
        $incidentes = $this->crearIncidentes(2, ['usuario_asignado_id' => $agente->id]);
        $soloEstado = $this->iniciarSesion($agente);

        $this->conToken($soloEstado)->patchJson('/api/v1/incidentes/lote', ['ids' => $this->ids($incidentes), 'cambios' => ['prioridad' => 'alta']])->assertForbidden();
        $this->conToken($soloEstado)->patchJson('/api/v1/incidentes/lote', ['ids' => $this->ids($incidentes), 'cambios' => ['estado' => 'en_progreso']])->assertOk();
    }

    public function test_si_uno_lo_atiende_otra_persona_no_se_modifica_ninguno(): void
    {
        [$propio] = $this->crearIncidentes(1);
        [$deOtro] = $this->crearIncidentes(1, ['usuario_asignado_id' => $this->crearUsuario('Soporte N1')->id]);
        $agente = $this->iniciarSesion($this->usuarioConPermisos(ClavePermiso::VerTodos, ClavePermiso::CambiarEstado));

        $this->conToken($agente)->patchJson('/api/v1/incidentes/lote', ['ids' => [$propio->id, $deOtro->id], 'cambios' => ['estado' => 'en_progreso']])
            ->assertForbidden();

        $this->assertSame(Estado::Abierto, $propio->fresh()->estado);
        $this->assertSame(Estado::Abierto, $deOtro->fresh()->estado);
    }

    public function test_sin_permiso_de_modificar_nada_la_ruta_responde_403(): void
    {
        $incidentes = $this->crearIncidentes(1);
        $lector = $this->iniciarSesion($this->usuarioConPermisos(ClavePermiso::VerTodos));

        $this->conToken($lector)->patchJson('/api/v1/incidentes/lote', ['ids' => $this->ids($incidentes), 'cambios' => ['prioridad' => 'alta']])->assertForbidden();
        $this->conToken($lector)->deleteJson('/api/v1/incidentes/lote', ['ids' => $this->ids($incidentes)])->assertForbidden();
        $this->sinCredencial()->patchJson('/api/v1/incidentes/lote', [])->assertUnauthorized();
    }

    public function test_valida_la_lista_de_ids_y_los_cambios(): void
    {
        $this->conToken($this->token)->patchJson('/api/v1/incidentes/lote', ['ids' => [], 'cambios' => []])
            ->assertUnprocessable()->assertJsonValidationErrors(['ids', 'cambios'], 'errors');
        $this->conToken($this->token)->patchJson('/api/v1/incidentes/lote', ['ids' => [1, 1, 'x'], 'cambios' => ['estado' => 'volar', 'titulo' => 'No se cambia en lote']])
            ->assertUnprocessable()->assertJsonValidationErrors(['ids.1', 'ids.2', 'cambios.estado'], 'errors');
        $this->conToken($this->token)->patchJson('/api/v1/incidentes/lote', ['ids' => range(1, 101), 'cambios' => ['prioridad' => 'alta']])
            ->assertUnprocessable()->assertJsonValidationErrors(['ids'], 'errors');
    }

    public function test_los_campos_que_no_son_de_lote_se_descartan(): void
    {
        [$incidente] = $this->crearIncidentes(1);
        $titulo = $incidente->titulo;

        $this->conToken($this->token)->patchJson('/api/v1/incidentes/lote', ['ids' => [$incidente->id], 'cambios' => ['prioridad' => 'alta', 'titulo' => 'Otro título']])->assertOk();

        $this->assertSame($titulo, $incidente->fresh()->titulo);
    }

    public function test_borra_varios_con_borrado_logico_e_historial(): void
    {
        $incidentes = $this->crearIncidentes(3);

        $this->conToken($this->token)->deleteJson('/api/v1/incidentes/lote', ['ids' => $this->ids($incidentes)])->assertNoContent();

        foreach ($incidentes as $incidente) {
            $this->assertSoftDeleted($incidente);
            $this->assertSame(1, HistorialIncidente::where('incidente_id', $incidente->id)->where('evento', EventoHistorial::Borrado->value)->count());
        }
    }

    public function test_borrar_exige_poder_borrar_cada_uno(): void
    {
        $incidentes = $this->crearIncidentes(2);
        $sinBorrar = $this->usuarioConPermisos(ClavePermiso::VerTodos, ClavePermiso::Borrar);
        // Con el permiso de borrar pero sin ver lo ajeno: el lote responde no encontrado.
        $soloPropio = $this->usuarioConPermisos(ClavePermiso::VerPropios, ClavePermiso::Borrar);
        $propio = $this->incidenteDe($soloPropio);

        $this->conToken($this->iniciarSesion($soloPropio))->deleteJson('/api/v1/incidentes/lote', ['ids' => [$propio->id, $incidentes[0]->id]])->assertNotFound();
        $this->conToken($this->iniciarSesion($soloPropio))->deleteJson('/api/v1/incidentes/lote', ['ids' => [$propio->id]])->assertNoContent();
        $this->conToken($this->iniciarSesion($sinBorrar))->deleteJson('/api/v1/incidentes/lote', ['ids' => $this->ids($incidentes)])->assertNoContent();

        $this->assertSoftDeleted($propio);
    }

    public function test_la_ruta_de_lote_no_se_confunde_con_un_incidente(): void
    {
        $this->conToken($this->token)->getJson('/api/v1/incidentes/lote')->assertNotFound();
    }
}
