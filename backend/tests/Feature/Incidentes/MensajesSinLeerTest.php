<?php

namespace Tests\Feature\Incidentes;

use App\Identidad\Models\User;
use App\Incidentes\Models\Incidente;
use Tests\TestCase;

// Mensajes sin leer: lo que otra persona escribió en la conversación después de la última lectura de quien
// pregunta. PUT /incidentes/{id}/lectura la deja leída.
class MensajesSinLeerTest extends TestCase
{
    private function escribir(Incidente $incidente, User $autor, bool $interno = false): void
    {
        $incidente->mensajes()->create(['usuario_id' => $autor->id, 'cuerpo' => 'Hola', 'interno' => $interno]);
    }

    public function test_cuenta_lo_que_escribio_otra_persona_y_al_leer_queda_en_cero(): void
    {
        $solicitante = $this->crearUsuario();
        $soporte = $this->crearUsuario('Soporte N1');
        $incidente = $this->incidenteDe($solicitante, ['usuario_asignado_id' => $soporte->id]);
        $this->escribir($incidente, $soporte);
        $this->escribir($incidente, $soporte);
        $this->escribir($incidente, $solicitante);
        $token = $this->iniciarSesion($solicitante);

        $this->conToken($token)->getJson("/api/v1/incidentes/{$incidente->id}")->assertJsonPath('data.mensajes_sin_leer', 2);
        $this->conToken($token)->getJson('/api/v1/incidentes')->assertJsonPath('data.0.mensajes_sin_leer', 2);

        $this->conToken($token)->putJson("/api/v1/incidentes/{$incidente->id}/lectura")->assertNoContent();
        $this->conToken($token)->getJson("/api/v1/incidentes/{$incidente->id}")->assertJsonPath('data.mensajes_sin_leer', 0);

        // Lo que llega después de leer vuelve a contar.
        $this->escribir($incidente, $soporte);
        $this->conToken($token)->getJson("/api/v1/incidentes/{$incidente->id}")->assertJsonPath('data.mensajes_sin_leer', 1);
    }

    public function test_las_notas_internas_no_cuentan_para_quien_no_puede_leerlas(): void
    {
        $solicitante = $this->crearUsuario();
        $soporte = $this->crearUsuario('Soporte N1');
        $incidente = $this->incidenteDe($solicitante, ['usuario_asignado_id' => $soporte->id]);
        $this->escribir($incidente, $soporte, interno: true);

        $this->conToken($this->iniciarSesion($solicitante))->getJson("/api/v1/incidentes/{$incidente->id}")->assertJsonPath('data.mensajes_sin_leer', 0);
        $this->conToken($this->iniciarSesion($this->crearUsuario('Soporte N2')))->getJson("/api/v1/incidentes/{$incidente->id}")->assertJsonPath('data.mensajes_sin_leer', 1);
    }

    public function test_cada_persona_lleva_su_propia_lectura_y_leer_dos_veces_no_falla(): void
    {
        $solicitante = $this->crearUsuario();
        $soporte = $this->crearUsuario('Soporte N1');
        $incidente = $this->incidenteDe($solicitante, ['usuario_asignado_id' => $soporte->id]);
        $this->escribir($incidente, $solicitante);
        $tokenDeSoporte = $this->iniciarSesion($soporte);

        $this->conToken($this->iniciarSesion($solicitante))->putJson("/api/v1/incidentes/{$incidente->id}/lectura")->assertNoContent();
        $this->conToken($tokenDeSoporte)->getJson("/api/v1/incidentes/{$incidente->id}")->assertJsonPath('data.mensajes_sin_leer', 1);

        $this->conToken($tokenDeSoporte)->putJson("/api/v1/incidentes/{$incidente->id}/lectura")->assertNoContent();
        $this->conToken($tokenDeSoporte)->putJson("/api/v1/incidentes/{$incidente->id}/lectura")->assertNoContent();
        $this->conToken($tokenDeSoporte)->getJson("/api/v1/incidentes/{$incidente->id}")->assertJsonPath('data.mensajes_sin_leer', 0);
    }

    public function test_solo_marca_leido_quien_ve_el_incidente(): void
    {
        $incidente = $this->incidenteDe($this->crearUsuario());

        $this->conToken($this->iniciarSesion($this->crearUsuario()))->putJson("/api/v1/incidentes/{$incidente->id}/lectura")->assertNotFound();
        $this->sinCredencial()->putJson("/api/v1/incidentes/{$incidente->id}/lectura")->assertUnauthorized();
    }
}
