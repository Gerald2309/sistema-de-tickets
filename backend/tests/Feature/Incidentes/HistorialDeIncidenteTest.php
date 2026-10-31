<?php

namespace Tests\Feature\Incidentes;

use App\Identidad\Dominio\ClavePermiso;
use Tests\TestCase;

class HistorialDeIncidenteTest extends TestCase
{
    public function test_muestra_la_creacion_la_toma_los_cambios_y_el_borrado_del_mas_reciente_al_mas_antiguo(): void
    {
        $solicitante = $this->crearUsuario();
        $soporte = $this->crearUsuario('Soporte N1');
        $administrador = $this->crearUsuario('Administrador');

        $id = $this->conToken($this->iniciarSesion($solicitante))->postJson('/api/v1/incidentes', [
            'titulo' => 'No imprime',
            'descripcion' => 'La impresora no responde.',
            'fecha_vencimiento' => '2030-01-15T10:00:00Z',
        ])->json('data.id');

        $tokenDeSoporte = $this->iniciarSesion($soporte);
        $this->conToken($tokenDeSoporte)->postJson("/api/v1/incidentes/$id/tomar")->assertOk();
        $this->conToken($tokenDeSoporte)->patchJson("/api/v1/incidentes/$id", ['estado' => 'en_progreso', 'prioridad' => 'alta'])->assertOk();
        $this->conToken($this->iniciarSesion($administrador))->deleteJson("/api/v1/incidentes/$id")->assertNoContent();

        $respuesta = $this->conToken($this->iniciarSesion($administrador))->getJson("/api/v1/incidentes/$id/historial")
            ->assertOk()
            ->assertJsonCount(5, 'data');

        $this->assertSame(['borrado', 'modificacion', 'modificacion', 'modificacion', 'creacion'], array_column($respuesta->json('data'), 'evento'));
        $this->assertSame([null, 'prioridad', 'estado', 'usuario_asignado', null], array_column($respuesta->json('data'), 'campo'));
        $this->assertSame([$administrador->id, $soporte->id, $soporte->id, $soporte->id, $solicitante->id], array_column(array_column($respuesta->json('data'), 'autor'), 'id'));
        $this->assertSame([null, (string) $soporte->id], [$respuesta->json('data.3.valor_anterior'), $respuesta->json('data.3.valor_nuevo')]);
        $this->assertSame(['abierto', 'en_progreso'], [$respuesta->json('data.2.valor_anterior'), $respuesta->json('data.2.valor_nuevo')]);
        $this->assertNull($respuesta->json('data.4.valor_anterior'));
        $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}Z$/', $respuesta->json('data.4.created_at'));
        $this->assertNull($respuesta->json('meta.next_cursor'));
    }

    public function test_entrega_el_historial_por_tramos_y_ver_mas_trae_los_anteriores(): void
    {
        $soporte = $this->crearUsuario('Soporte N1');
        $incidente = $this->incidenteDe($soporte, ['usuario_asignado_id' => $soporte->id]);
        $token = $this->iniciarSesion($soporte);
        foreach (range(1, 12) as $n) {
            $this->conToken($token)->patchJson("/api/v1/incidentes/{$incidente->id}", ['prioridad' => $n % 2 ? 'alta' : 'baja'])->assertOk();
        }

        $primera = $this->conToken($token)->getJson("/api/v1/incidentes/{$incidente->id}/historial")->assertOk()->assertJsonCount(10, 'data');
        $cursor = $primera->json('meta.next_cursor');
        $this->assertNotNull($cursor);

        $segunda = $this->conToken($token)->getJson("/api/v1/incidentes/{$incidente->id}/historial?cursor={$cursor}")->assertOk()->assertJsonCount(2, 'data');
        $this->assertNull($segunda->json('meta.next_cursor'));
        // Juntos, los dos tramos son todo el historial, sin repetir y del más reciente al más antiguo.
        $ids = [...array_column($primera->json('data'), 'id'), ...array_column($segunda->json('data'), 'id')];
        $ordenados = $ids;
        rsort($ordenados);
        $this->assertSame($ordenados, $ids);
        $this->assertCount(12, array_unique($ids));
    }

    public function test_un_incidente_sin_cambios_registrados_devuelve_una_lista_vacia(): void
    {
        $incidente = $this->incidenteDe($this->crearUsuario());
        $token = $this->iniciarSesion($this->crearUsuario('Soporte N1'));

        $this->conToken($token)->getJson("/api/v1/incidentes/{$incidente->id}/historial")
            ->assertOk()
            ->assertJsonCount(0, 'data')
            ->assertJsonPath('meta.next_cursor', null);
    }

    public function test_el_creador_ve_el_historial_de_su_incidente(): void
    {
        $creador = $this->crearUsuario();
        $incidente = $this->incidenteDe($creador);

        $this->conToken($this->iniciarSesion($creador))->getJson("/api/v1/incidentes/{$incidente->id}/historial")->assertOk();
    }

    public function test_el_historial_de_un_incidente_ajeno_responde_404(): void
    {
        $incidente = $this->incidenteDe($this->crearUsuario());
        $token = $this->iniciarSesion($this->crearUsuario());

        $this->conToken($token)->getJson("/api/v1/incidentes/{$incidente->id}/historial")->assertNotFound();
    }

    public function test_quien_ve_el_incidente_pero_no_tiene_el_permiso_recibe_403(): void
    {
        $sinHistorial = $this->usuarioConPermisos(ClavePermiso::CrearIncidente, ClavePermiso::VerPropios);
        $incidente = $this->incidenteDe($sinHistorial);

        $this->conToken($this->iniciarSesion($sinHistorial))->getJson("/api/v1/incidentes/{$incidente->id}/historial")->assertForbidden();
    }
}
