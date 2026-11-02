<?php

namespace Tests\Feature\Seguridad;

use App\Http\Controllers\DocumentacionController;
use App\Http\Errores\DocumentarProblemas;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DocumentacionDeApiTest extends TestCase
{
    /** Generarlo tarda y consume memoria: una vez para toda la clase. */
    private static ?string $archivo = null;

    protected function setUp(): void
    {
        parent::setUp();

        // El archivo que exporta app:preparar no se toca: las pruebas usan un disco aparte, con el documento ya
        // generado para que cada prueba no lo vuelva a construir.
        Storage::fake('local');
        if (self::$archivo !== null) {
            Storage::disk('local')->put(DocumentacionController::ARCHIVO, self::$archivo);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function contrato(): array
    {
        $contrato = $this->getJson('/api/v1/openapi.json')->assertOk()->json();
        self::$archivo ??= Storage::disk('local')->get(DocumentacionController::ARCHIVO);

        return $contrato;
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

    public function test_agrupa_por_recurso_dice_que_exige_y_no_publica_lo_interno_de_la_interfaz(): void
    {
        $contrato = $this->contrato();

        $this->assertSame(
            ['Autenticación y cuenta', 'Incidentes', 'Conversación', 'Panel', 'Usuarios y roles', 'Empresas y SLA', 'Auditoría'],
            array_column($contrato['tags'], 'name'),
        );
        $this->assertSame(['Incidentes'], $contrato['paths']['/incidentes']['get']['tags']);
        $this->assertStringContainsString('**Requiere:** permiso `ver_auditoria`.', $contrato['paths']['/auditoria']['get']['description']);
        foreach (['/avisos', '/mi-dia/plan', '/broadcasting/auth', '/documentacion'] as $interna) {
            $this->assertArrayNotHasKey($interna, $contrato['paths'], "{$interna} es de uso interno.");
        }
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

        $this->assertMatchesRegularExpression('/<script src="https:\/\/cdn\.jsdelivr\.net\/npm\/@scalar\/api-reference@[\d.]+\/[^"]+"\s+integrity="sha384-[^"]+"/', $respuesta->getContent());
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
