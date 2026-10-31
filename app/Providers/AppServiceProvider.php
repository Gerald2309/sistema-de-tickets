<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Middleware\TrustProxies;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        // Detrás de un proxy que termina el TLS: sus direcciones (separadas por comas, o `*`) salen de TRUSTED_PROXIES.
        // Sin ella no se confía en ninguna cabecera X-Forwarded-*. Se lee de la configuración, no del entorno:
        // con la configuración en caché, env() fuera de config/ devuelve null.
        $proxies = trim((string) config('seguridad.proxies_confiables'));
        if ($proxies !== '') {
            TrustProxies::at(in_array($proxies, ['*', '**'], true) ? $proxies : array_map('trim', explode(',', $proxies)));
        }

        // Una consulta por relación cargada de antemano: fuera de producción, el N+1 lanza una excepción.
        Model::preventLazyLoading(! $this->app->isProduction());

        // Una sola definición de contraseña segura (ASVS 6.2.1 y 6.2.5): longitud, sin reglas de composición.
        Password::defaults(fn () => Password::min(15)->max(256));

        $this->definirLimitesDePeticiones();
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
