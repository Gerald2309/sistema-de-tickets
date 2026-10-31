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
        // Es una API sin páginas: quien llega sin credencial recibe un 401, nunca una redirección a un
        // formulario de login (que no existe), aunque no pida JSON.
        $middleware->redirectGuestsTo(fn () => null);
        $middleware->appendToGroup('api', CabecerasDeApi::class);
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
