<?php

namespace Tests\Feature\Seguridad;

use App\Http\Errores\DocumentarProblemas;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DocumentacionDeApiTest extends TestCase
{
    /** @var array<string, mixed>|null generarlo tarda: una vez para toda la clase */
    private static ?array $contrato = null;

    protected function setUp(): void
    {
        parent::setUp();

        // El archivo que exporta app:preparar no se toca: se genera en un disco aparte.
        Storage::fake('local');
    }

    /**
     * @return array<string, mixed>
     */
    private function contrato(): array
    {
        return self::$contrato ??= $this->getJson('/api/v1/openapi.json')->assertOk()->json();
    }

    public function test_publica_el_contrato_de_la_api_con_autenticacion_por_token(): void
    {
        $contrato = $this->contrato();

        $this->assertStringStartsWith('3.1', $contrato['openapi']);
        $this->assertArrayHasKey('/incidentes', $contrato['paths']);
        $this->assertSame([['http' => []]], $contrato['security']);
        $this->assertSame('bearer', $contrato['components']['securitySchemes']['http']['scheme']);
        $this->assertSame([], $contrato['paths']['/auth/login']['post']['security'], 'Iniciar sesión no pide token.');
    }

    public function test_describe_los_errores_como_los_entrega_la_api(): void
    {
        $respuestas = $this->contrato()['components']['responses'];

        foreach ($respuestas as $respuesta) {
            $this->assertSame([DocumentarProblemas::TIPO], array_keys($respuesta['content']));
        }
        $this->assertArrayHasKey('errors', $respuestas['ValidationException']['content'][DocumentarProblemas::TIPO]['schema']['properties']);
    }

    public function test_el_visor_carga_su_libreria_con_integridad_y_una_politica_que_lo_limita(): void
    {
        $respuesta = $this->get('/api/v1/documentacion')->assertOk();

        $this->assertMatchesRegularExpression('/<script src="https:\/\/unpkg\.com\/[^"]+" integrity="sha384-[^"]+"/', $respuesta->getContent());
        $politica = (string) $respuesta->headers->get('Content-Security-Policy');
        $this->assertStringContainsString("frame-ancestors 'none'", $politica);
        $this->assertStringContainsString("form-action 'none'", $politica);
        $this->assertStringContainsString("connect-src 'self'", $politica);
    }

    public function test_se_puede_cerrar_en_un_despliegue(): void
    {
        config(['scramble.publica' => false]);

        $this->getJson('/api/v1/openapi.json')->assertForbidden();
        $this->get('/api/v1/documentacion')->assertForbidden();
    }
}
