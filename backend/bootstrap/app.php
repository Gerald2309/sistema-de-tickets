<?php

use App\Http\Errores\ProblemaHttp;
use App\Http\Middleware\CabecerasDeApi;
use App\Http\Middleware\ExigirAutorizacionDeclarada;
use App\Http\Middleware\IdentificarPeticion;
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
        // Primeros de todos: también los rechazos por límite de peticiones o por credencial llevan su identificador
        // y las cabeceras de seguridad (las rutas inexistentes las reciben de ProblemaHttp).
        $middleware->prependToGroup('api', [IdentificarPeticion::class, CabecerasDeApi::class]);
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
