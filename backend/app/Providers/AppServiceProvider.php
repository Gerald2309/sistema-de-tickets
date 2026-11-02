<?php

namespace App\Providers;

use App\Auditoria\Acciones\RegistrarAuditoria;
use App\Auditoria\Dominio\AccionAuditoria;
use App\Http\Controllers\DocumentacionController;
use App\Http\Errores\DocumentarProblemas;
use App\Incidentes\Acciones\AvisarCambios;
use App\Incidentes\Consultas\CacheDelPanel;
use App\Incidentes\Eventos\AcuerdoDeServicioCambiado;
use App\Incidentes\Eventos\IncidentesCambiados;
use Dedoc\Scramble\Scramble;
use Dedoc\Scramble\Support\Generator\OpenApi;
use Dedoc\Scramble\Support\Generator\SecurityScheme;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Http\Middleware\TrustProxies;
use Illuminate\Http\Request;
use Illuminate\Routing\Route as RutaDeLaApi;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Una instancia por petición: junta los avisos de tiempo real de esa petición (F-12).
        $this->app->scoped(AvisarCambios::class);

        // Sus rutas van dentro de /api/v1 (las registra documentarLaApi): así llegan por la misma entrada que la API.
        Scramble::ignoreDefaultRoutes();
    }

    public function boot(): void
    {
        // Canales del tiempo real. Su ruta de autorización está en routes/api.php, con token y límite
        // como el resto de la API (no en el grupo web, que usaría sesión).
        require base_path('routes/channels.php');

        // Detrás de un proxy que termina el TLS: sus direcciones (separadas por comas, o `*`) salen de TRUSTED_PROXIES.
        // Sin ella no se confía en ninguna cabecera X-Forwarded-*. Se lee de la configuración, no del entorno:
        // con la configuración en caché, env() fuera de config/ devuelve null.
        $proxies = trim((string) config('seguridad.proxies_confiables'));
        if ($proxies !== '') {
            TrustProxies::at(in_array($proxies, ['*', '**'], true) ? $proxies : array_map('trim', explode(',', $proxies)));
        }

        // Una consulta por relación cargada de antemano: fuera de producción, el N+1 lanza una excepción.
        Model::preventLazyLoading(! $this->app->isProduction());

        // Una sola definición de contraseña segura: larga, con mayúscula, minúscula, número y símbolo, y que no
        // aparezca en filtraciones (sin enviarla: solo viaja el inicio de su hash). Si ese servicio no responde, la
        // comprobación se omite y no impide guardar.
        Password::defaults(function () {
            $regla = Password::min(15)->max(256)->mixedCase()->numbers()->symbols();

            return config('seguridad.contrasenas.comprobar_filtradas') ? $regla->uncompromised() : $regla;
        });

        $this->definirLimitesDePeticiones();
        $this->documentarLaApi();
        $this->vigilarConsultasLentas();

        // Un incidente que cambia deja atrás los cálculos guardados del panel.
        Event::listen(IncidentesCambiados::class, fn () => app(CacheDelPanel::class)->olvidar());
        // Incidentes y Auditoría no se conocen: el cambio de un SLA se une aquí con su registro.
        Event::listen(AcuerdoDeServicioCambiado::class, function (AcuerdoDeServicioCambiado $cambio): void {
            foreach ($cambio->cambios as $detalle) {
                app(RegistrarAuditoria::class)->ejecutar(
                    AccionAuditoria::AcuerdoDeServicioModificado, $cambio->autorId, null, "SLA de «{$cambio->empresa}», {$detalle}", $cambio->ip,
                );
            }
        });
    }

    private function vigilarConsultasLentas(): void
    {
        DB::listen(function (QueryExecuted $consulta): void {
            if ($consulta->time >= (int) config('observabilidad.consulta_lenta_ms')) {
                Log::warning('Consulta lenta', ['sql' => $consulta->sql, 'ms' => round((float) $consulta->time, 1)]);
            }
        });
    }

    private function documentarLaApi(): void
    {
        // Sin sesión, como cualquiera que abra el enlace: Gate solo consulta a invitados si el usuario admite null.
        Gate::define('viewApiDocs', fn (?Authenticatable $persona = null) => (bool) config('scramble.publica'));

        Scramble::configure()->withDocumentTransformers([
            fn (OpenApi $openApi) => $openApi->secure(SecurityScheme::http('bearer')),
            DocumentarProblemas::class,
        ]);

        // La documentación no se lista a sí misma.
        Scramble::configure()->routes(fn (RutaDeLaApi $ruta) => str_starts_with($ruta->uri, 'api/v1/')
            && ! in_array($ruta->uri, ['api/v1/documentacion', 'api/v1/openapi.json'], true));

        Route::prefix('api/v1')->middleware(config('scramble.middleware'))->group(function (): void {
            Route::get('documentacion', [DocumentacionController::class, 'visor']);
            Route::get('openapi.json', [DocumentacionController::class, 'contrato']);
        });
    }

    private function definirLimitesDePeticiones(): void
    {
        RateLimiter::for('api', fn (Request $request) => Limit::perMinute((int) config('seguridad.limites.api'))
            ->by($request->user()?->id ?: $request->ip()));

        // Dos límites a la vez: por correo (fuerza bruta sobre una cuenta) y por IP (rociado de correos).
        RateLimiter::for('login', fn (Request $request) => [
            Limit::perMinute((int) config('seguridad.limites.login_email'))
                ->by('email:'.Str::lower(trim((string) $request->input('email')))),
            Limit::perMinute((int) config('seguridad.limites.login_ip'))->by('ip:'.$request->ip()),
        ]);
    }
}
