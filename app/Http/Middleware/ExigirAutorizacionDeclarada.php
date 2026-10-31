<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Auth\Middleware\Authorize;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

// Denegar por defecto: una ruta autenticada que no declare su permiso con ->can() queda cerrada.
class ExigirAutorizacionDeclarada
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $this->declaraPermiso($request)) {
            Log::warning('Ruta autenticada sin permiso declarado.', ['ruta' => $request->route()?->uri()]);

            abort(403);
        }

        return $next($request);
    }

    private function declaraPermiso(Request $request): bool
    {
        foreach ($request->route()?->gatherMiddleware() ?? [] as $middleware) {
            if (is_string($middleware) && (str_starts_with($middleware, 'can:') || str_starts_with($middleware, Authorize::class.':'))) {
                return true;
            }
        }

        return false;
    }
}
