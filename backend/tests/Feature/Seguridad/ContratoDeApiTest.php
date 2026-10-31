<?php

namespace Tests\Feature\Seguridad;

use App\Http\Middleware\CabecerasDeApi;
use Illuminate\Routing\Route as RutaDeLaApi;
use Illuminate\Support\Facades\Route;
use RuntimeException;
use Tests\TestCase;

// El contrato transversal de la API: denegar por defecto, formato de errores y cabeceras.
class ContratoDeApiTest extends TestCase
{
    private const SESION_O_CSRF = ['EnsureFrontendRequestsAreStateful', 'PreventRequestForgery', 'StartSession'];

    public function test_toda_ruta_de_la_api_salvo_el_login_exige_token_limite_y_permiso_declarado(): void
    {
        $rutas = $this->rutasDeLaApi();

        $this->assertNotEmpty($rutas);

        foreach ($rutas as $ruta) {
            $middleware = $ruta->gatherMiddleware();

            if ($ruta->uri() === 'api/v1/auth/login') {
                $this->assertContains('throttle:login', $middleware);
                $this->assertNotContains('auth:sanctum', $middleware);

                continue;
            }

            foreach (['auth:sanctum', 'throttle:api', 'autorizacion.declarada'] as $esperado) {
                $this->assertContains($esperado, $middleware, "{$ruta->uri()} no lleva $esperado.");
            }

            $this->assertNotEmpty(
                array_filter($middleware, fn ($m) => is_string($m) && str_starts_with($m, 'can:')),
                "{$ruta->uri()} no declara su permiso con ->can().",
            );
        }
    }

    public function test_una_ruta_sin_permiso_declarado_queda_cerrada(): void
    {
        Route::middleware(['api', 'auth:sanctum', 'throttle:api', 'autorizacion.declarada'])->group(function () {
            Route::get('api/v1/prueba/olvidada', fn () => 'abierta');
            Route::get('api/v1/prueba/declarada', fn () => 'abierta')->can('autenticado');
        });

        $token = $this->iniciarSesion($this->crearUsuario('Administrador'));

        $this->conToken($token)->getJson('/api/v1/prueba/olvidada')
            ->assertForbidden()
            ->assertHeader('Content-Type', 'application/problem+json');

        $this->conToken($token)->getJson('/api/v1/prueba/declarada')->assertOk();
    }

    public function test_un_usuario_sin_el_permiso_recibe_403(): void
    {
        Route::middleware(['api', 'auth:sanctum', 'throttle:api', 'autorizacion.declarada'])
            ->get('api/v1/prueba/solo-administracion', fn () => 'ok')
            ->can('administrar_roles');

        $solicitante = $this->iniciarSesion($this->crearUsuario('Solicitante'));
        $administrador = $this->iniciarSesion($this->crearUsuario('Administrador'));

        // Los mensajes tratan de usted, como la interfaz.
        $this->conToken($solicitante)->getJson('/api/v1/prueba/solo-administracion')->assertForbidden()
            ->assertJsonPath('detail', 'No tiene permiso para realizar esta acción.');
        $this->conToken($administrador)->getJson('/api/v1/prueba/solo-administracion')->assertOk();
    }

    public function test_sin_credencial_responde_401_aunque_el_cliente_no_pida_json(): void
    {
        $this->sinCredencial()->get('/api/v1/me', ['Accept' => 'text/html'])
            ->assertUnauthorized()
            ->assertHeader('Content-Type', 'application/problem+json');
    }

    public function test_sin_credencial_se_responde_401_antes_de_buscar_el_recurso(): void
    {
        $incidente = $this->incidenteDe($this->crearUsuario());
        $borrado = $this->incidenteDe($this->crearUsuario());
        $borrado->delete();

        // Si se buscara primero el recurso, un id que existe (401) se distinguiría de uno que no (404)
        // y cualquiera podría descubrir qué incidentes existen sin iniciar sesión.
        foreach ([$incidente->id, $borrado->id, 999999] as $id) {
            $this->sinCredencial()->getJson("/api/v1/incidentes/$id")->assertUnauthorized();
            $this->sinCredencial()->getJson("/api/v1/incidentes/$id/historial")->assertUnauthorized();
            $this->sinCredencial()->patchJson("/api/v1/incidentes/$id", ['titulo' => 'x'])->assertUnauthorized();
            $this->sinCredencial()->deleteJson("/api/v1/incidentes/$id")->assertUnauthorized();
        }
    }

    public function test_la_api_no_usa_sesion_ni_proteccion_csrf(): void
    {
        $grupo = implode(' ', Route::getMiddlewareGroups()['api'] ?? []);

        foreach (self::SESION_O_CSRF as $middleware) {
            $this->assertStringNotContainsString($middleware, $grupo);

            foreach ($this->rutasDeLaApi() as $ruta) {
                $resuelto = implode(' ', Route::gatherRouteMiddleware($ruta));

                $this->assertStringNotContainsString($middleware, $resuelto, "{$ruta->uri()} usa $middleware.");
            }
        }
    }

    public function test_los_errores_salen_como_problem_json(): void
    {
        $this->getJson('/api/v1/no-existe')
            ->assertNotFound()
            ->assertHeader('Content-Type', 'application/problem+json')
            ->assertExactJson([
                'type' => 'about:blank',
                'title' => 'Not Found',
                'status' => 404,
                'detail' => 'El recurso no existe o no está disponible.',
            ]);

        $this->getJson('/api/v1/auth/login')
            ->assertStatus(405)
            ->assertHeader('Content-Type', 'application/problem+json')
            ->assertHeader('Allow');
    }

    public function test_un_error_inesperado_responde_500_sin_revelar_detalles(): void
    {
        Route::middleware('api')->get('api/v1/prueba/falla', fn () => throw new RuntimeException('secreto interno de la base de datos'));

        $respuesta = $this->getJson('/api/v1/prueba/falla')
            ->assertStatus(500)
            ->assertHeader('Content-Type', 'application/problem+json')
            ->assertJsonPath('detail', 'Ocurrió un error inesperado.');

        $this->assertStringNotContainsString('secreto', $respuesta->getContent());
        $this->assertArrayNotHasKey('trace', $respuesta->json());
        $this->assertArrayNotHasKey('exception', $respuesta->json());
    }

    public function test_las_respuestas_de_la_api_no_se_guardan_en_cache(): void
    {
        $usuario = $this->crearUsuario();

        $this->postJson('/api/v1/auth/login', ['email' => $usuario->email, 'password' => self::PASSWORD])
            ->assertOk()
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('Cache-Control', 'no-store, private');
    }

    public function test_las_respuestas_de_error_llevan_las_mismas_cabeceras_de_seguridad(): void
    {
        $token = $this->iniciarSesion($this->crearUsuario());

        $errores = [
            401 => $this->sinCredencial()->getJson('/api/v1/incidentes'),
            403 => $this->conToken($token)->getJson('/api/v1/usuarios'),
            404 => $this->conToken($token)->getJson('/api/v1/incidentes/999999'),
            422 => $this->sinCredencial()->postJson('/api/v1/auth/login', []),
        ];

        foreach ($errores as $estado => $respuesta) {
            $respuesta->assertStatus($estado);
            foreach (CabecerasDeApi::CABECERAS as $nombre => $valor) {
                $this->assertStringContainsString($valor, $respuesta->headers->get($nombre), "{$estado}: falta {$nombre}");
            }
        }

        $this->assertSame("default-src 'none'; frame-ancestors 'none'", $errores[401]->headers->get('Content-Security-Policy'));
    }

    /**
     * @return list<RutaDeLaApi>
     */
    private function rutasDeLaApi(): array
    {
        return array_values(array_filter(
            Route::getRoutes()->getRoutes(),
            fn (RutaDeLaApi $ruta) => str_starts_with($ruta->uri(), 'api/v1/') && ! str_contains($ruta->uri(), '/prueba/'),
        ));
    }
}
