<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
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
