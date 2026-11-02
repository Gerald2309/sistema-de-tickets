<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

// Una línea de log por petición, con lo que sirve para medir y para investigar: qué ruta, cómo terminó, cuánto
// tardó y cuántas consultas hizo. Lleva el identificador de correlación (Context lo agrega). No registra la
// consulta de la URL ni el cuerpo: pueden traer datos personales.
class RegistrarPeticion
{
    public function handle(Request $request, Closure $next): Response
    {
        $inicio = hrtime(true);
        $consultas = 0;
        $milisegundosEnBase = 0.0;

        DB::listen(function (QueryExecuted $consulta) use (&$consultas, &$milisegundosEnBase): void {
            $consultas++;
            $milisegundosEnBase += (float) $consulta->time;
        });

        $respuesta = $next($request);

        $duracion = round((hrtime(true) - $inicio) / 1e6, 1);
        $lenta = $duracion >= (int) config('observabilidad.peticion_lenta_ms');

        Log::log($lenta ? 'warning' : 'info', $lenta ? 'Petición lenta' : 'Petición atendida', [
            'metodo' => $request->method(),
            // La plantilla de la ruta, no la URL: agrupa las peticiones y no expone identificadores ni búsquedas.
            'ruta' => $request->route()?->uri() ?? 'desconocida',
            'estado' => $respuesta->getStatusCode(),
            'duracion_ms' => $duracion,
            'consultas' => $consultas,
            'consultas_ms' => round($milisegundosEnBase, 1),
            'usuario_id' => $request->user()?->getAuthIdentifier(),
        ]);

        return $respuesta;
    }
}
