<?php

use App\Http\Errores\ProblemaHttp;
use App\Http\Middleware\CabecerasDeApi;
use App\Http\Middleware\ExigirAutorizacionDeclarada;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias(['autorizacion.declarada' => ExigirAutorizacionDeclarada::class]);
        $middleware->appendToGroup('api', CabecerasDeApi::class);

        // Detrás de un proxy que termina el TLS: sus direcciones (separadas por comas, o `*`) salen de TRUSTED_PROXIES.
        // Sin ella no se confía en ninguna cabecera X-Forwarded-*.
        $middleware->trustProxies(at: array_filter(array_map('trim', explode(',', (string) env('TRUSTED_PROXIES', '')))) ?: null);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        // Todo error de la API sale como application/problem+json (RFC 9457).
        $exceptions->render(fn (Throwable $excepcion, Request $request) => $request->is('api/*')
            ? ProblemaHttp::desde($excepcion)
            : null);
    })->create();
