<?php

namespace Tests\Feature\Seguridad;

use App\Identidad\Dominio\ClavePermiso;
use Illuminate\Log\Events\MessageLogged;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class ObservabilidadTest extends TestCase
{
    /** @var list<MessageLogged> */
    private array $registros = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->prepararDatosBase();
        Event::listen(MessageLogged::class, fn (MessageLogged $registro) => $this->registros[] = $registro);
    }

    private function registro(string $mensaje): ?MessageLogged
    {
        return collect($this->registros)->first(fn (MessageLogged $registro) => $registro->message === $mensaje);
    }

    private function listarIncidentes(string $consulta = ''): void
    {
        $token = $this->iniciarSesion($this->usuarioConPermisos(ClavePermiso::VerTodos));
        $this->registros = [];

        $this->conToken($token)->getJson('/api/v1/incidentes'.$consulta)->assertOk();
    }

    public function test_cada_peticion_deja_una_linea_con_ruta_estado_duracion_consultas_y_quien(): void
    {
        $this->listarIncidentes();

        $registro = $this->registro('Petición atendida');
        $this->assertNotNull($registro);
        $this->assertSame('info', $registro->level);
        $this->assertSame('GET', $registro->context['metodo']);
        $this->assertSame('api/v1/incidentes', $registro->context['ruta']);
        $this->assertSame(200, $registro->context['estado']);
        $this->assertIsFloat($registro->context['duracion_ms']);
        $this->assertGreaterThan(0, $registro->context['consultas']);
        $this->assertIsInt($registro->context['usuario_id']);
    }

    public function test_no_registra_la_consulta_de_la_url_que_puede_traer_datos_personales(): void
    {
        $this->listarIncidentes('?q=nombre-de-alguien');

        $this->assertStringNotContainsString('nombre-de-alguien', (string) json_encode($this->registro('Petición atendida')?->context));
    }

    public function test_una_peticion_que_pasa_el_umbral_se_registra_como_lenta(): void
    {
        config(['observabilidad.peticion_lenta_ms' => 0]);

        $this->listarIncidentes();

        $this->assertSame('warning', $this->registro('Petición lenta')?->level);
    }

    public function test_una_consulta_lenta_se_registra_con_su_sql_pero_sin_los_valores(): void
    {
        config(['observabilidad.consulta_lenta_ms' => 0]);

        $this->listarIncidentes('?q=nombre-de-alguien');

        $lentas = collect($this->registros)->filter(fn (MessageLogged $registro) => $registro->message === 'Consulta lenta');
        $this->assertNotEmpty($lentas);
        $this->assertSame('warning', $lentas->first()->level);
        $this->assertStringNotContainsString('nombre-de-alguien', (string) json_encode($lentas->pluck('context')));
    }
}
