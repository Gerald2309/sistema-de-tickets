<?php

namespace App\Incidentes\Consultas;

use App\Incidentes\Dominio\AlcanceIncidentes;
use Closure;
use Illuminate\Support\Facades\Cache;

/**
 * Caché del panel: cada cálculo se guarda por lo que la persona puede ver (todo, lo suyo o nada), nunca por
 * persona cuando ve todo. Cuando cambia un incidente, una versión nueva deja atrás lo guardado; la duración solo
 * cubre lo que cambia con el reloj, como los vencidos.
 */
class CacheDelPanel
{
    private const VERSION = 'panel:version';

    /**
     * @template T
     *
     * @param  list<int|string>  $variante  lo que además distingue el cálculo, como la zona horaria
     * @param  Closure(): T  $calcular
     * @return T
     */
    public function recordar(string $nombre, AlcanceIncidentes $alcance, array $variante, int $segundos, Closure $calcular): mixed
    {
        $clave = implode(':', ['panel', $this->version(), $nombre, $this->ambito($alcance), ...$variante]);

        return Cache::remember($clave, $segundos, $calcular);
    }

    public function olvidar(): void
    {
        Cache::forever(self::VERSION, $this->version() + 1);
    }

    private function version(): int
    {
        return (int) Cache::get(self::VERSION, 0);
    }

    private function ambito(AlcanceIncidentes $alcance): string
    {
        return match (true) {
            $alcance->veTodo() => 'todo',
            $alcance->veSoloLoPropio() => 'de-'.$alcance->usuarioId(),
            default => 'nada',
        };
    }
}
