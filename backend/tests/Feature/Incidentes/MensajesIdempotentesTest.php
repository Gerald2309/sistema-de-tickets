<?php

namespace Tests\Feature\Incidentes;

use App\Identidad\Models\User;
use App\Incidentes\Acciones\EscribirMensaje;
use App\Incidentes\Models\Incidente;
use App\Incidentes\Models\MensajeIncidente;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class MensajesIdempotentesTest extends TestCase
{
    private User $solicitante;

    private Incidente $incidente;

    protected function setUp(): void
    {
        parent::setUp();

        $this->prepararDatosBase();
        Storage::fake(EscribirMensaje::DISCO);
        $this->solicitante = $this->crearUsuario();
        $this->incidente = $this->incidenteDe($this->solicitante);
    }

    /**
     * @param  array<string, mixed>  $cuerpo
     */
    private function escribir(User $autor, string $clave, array $cuerpo = ['cuerpo' => 'Sigue sin imprimir.']): TestResponse
    {
        return $this->conToken($this->iniciarSesion($autor))
            ->withHeader('Idempotency-Key', $clave)
            ->post("/api/v1/incidentes/{$this->incidente->id}/mensajes", $cuerpo, ['Accept' => 'application/json']);
    }

    public function test_reintentar_con_la_misma_clave_devuelve_el_mensaje_ya_guardado_sin_duplicarlo(): void
    {
        $clave = (string) Str::uuid();
        $imagen = fn (): array => ['cuerpo' => 'Así se ve.', 'imagenes' => [ConversacionDeIncidenteTest::png()]];

        $primero = $this->escribir($this->solicitante, $clave, $imagen())->assertCreated()->assertHeaderMissing('Idempotent-Replayed');
        $this->escribir($this->solicitante, $clave, $imagen())
            ->assertOk()
            ->assertHeader('Idempotent-Replayed', 'true')
            ->assertJsonPath('data.id', $primero->json('data.id'));

        $this->assertSame(1, MensajeIncidente::count());
        $this->assertCount(1, Storage::disk(EscribirMensaje::DISCO)->allFiles(), 'La imagen no se guarda dos veces.');
    }

    public function test_la_clave_es_de_cada_persona_y_sin_ella_cada_envio_es_un_mensaje(): void
    {
        $soporte = $this->crearUsuario('Soporte N1');
        $this->incidente->update(['usuario_asignado_id' => $soporte->id]);
        $clave = (string) Str::uuid();

        $this->escribir($this->solicitante, $clave)->assertCreated();
        $this->escribir($soporte, $clave)->assertCreated();
        // El cliente de pruebas conserva la cabecera de los envíos anteriores.
        $this->flushHeaders();
        $token = $this->iniciarSesion($this->solicitante);
        $this->conToken($token)->postJson("/api/v1/incidentes/{$this->incidente->id}/mensajes", ['cuerpo' => 'Hola.'])->assertCreated();
        $this->conToken($token)->postJson("/api/v1/incidentes/{$this->incidente->id}/mensajes", ['cuerpo' => 'Hola.'])->assertCreated();

        $this->assertSame(4, MensajeIncidente::count());
    }

    public function test_una_clave_que_no_es_un_uuid_se_rechaza(): void
    {
        $this->escribir($this->solicitante, 'no-es-un-uuid')
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['Idempotency-Key'], 'errors');

        $this->assertSame(0, MensajeIncidente::count());
    }
}
