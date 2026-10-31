<?php

namespace Tests\Feature\Incidentes;

use App\Identidad\Dominio\ClavePermiso;
use Tests\TestCase;

class HistorialDeIncidenteTest extends TestCase
{
    public function test_muestra_la_creacion_los_cambios_y_el_borrado_en_orden_cronologico(): void
    {
        $solicitante = $this->crearUsuario();
        $soporte = $this->crearUsuario('Soporte N1');
        $administrador = $this->crearUsuario('Administrador');

        $id = $this->conToken($this->iniciarSesion($solicitante))->postJson('/api/v1/incidentes', [
            'titulo' => 'No imprime',
            'descripcion' => 'La impresora no responde.',
            'fecha_vencimiento' => '2030-01-15T10:00:00Z',
        ])->json('data.id');

        $this->conToken($this->iniciarSesion($soporte))->patchJson("/api/v1/incidentes/$id", ['estado' => 'en_progreso', 'prioridad' => 'alta'])->assertOk();
        $this->conToken($this->iniciarSesion($administrador))->deleteJson("/api/v1/incidentes/$id")->assertNoContent();

        $respuesta = $this->conToken($this->iniciarSesion($administrador))->getJson("/api/v1/incidentes/$id/historial")
            ->assertOk()
            ->assertJsonCount(4, 'data');

        $this->assertSame(['creacion', 'modificacion', 'modificacion', 'borrado'], array_column($respuesta->json('data'), 'evento'));
        $this->assertSame([null, 'estado', 'prioridad', null], array_column($respuesta->json('data'), 'campo'));
        $this->assertSame([$solicitante->id, $soporte->id, $soporte->id, $administrador->id], array_column(array_column($respuesta->json('data'), 'autor'), 'id'));
        $this->assertSame(['abierto', 'en_progreso'], [$respuesta->json('data.1.valor_anterior'), $respuesta->json('data.1.valor_nuevo')]);
        $this->assertNull($respuesta->json('data.0.valor_anterior'));
        $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}Z$/', $respuesta->json('data.0.created_at'));
    }

    public function test_un_incidente_sin_cambios_registrados_devuelve_una_lista_vacia(): void
    {
        $incidente = $this->incidenteDe($this->crearUsuario());
        $token = $this->iniciarSesion($this->crearUsuario('Soporte N1'));

        $this->conToken($token)->getJson("/api/v1/incidentes/{$incidente->id}/historial")
            ->assertOk()
            ->assertExactJson(['data' => []]);
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

    public function test_sin_token_responde_401(): void
    {
        $incidente = $this->incidenteDe($this->crearUsuario());

        $this->getJson("/api/v1/incidentes/{$incidente->id}/historial")->assertUnauthorized();
    }
}
