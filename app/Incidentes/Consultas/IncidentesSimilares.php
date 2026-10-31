<?php

namespace App\Incidentes\Consultas;

use App\Identidad\Models\User;
use App\Incidentes\Dominio\Estado;
use App\Incidentes\Dominio\TerminosDeBusqueda;
use App\Incidentes\Models\Incidente;
use App\Incidentes\Policies\IncidentePolicy;
use Illuminate\Database\Eloquent\Collection;

class IncidentesSimilares
{
    public const MAXIMO_DE_RESULTADOS = 5;

    /**
     * Incidentes ya resueltos o cerrados, de la misma categoría, que comparten palabras con este,
     * del más al menos parecido. Busca por palabras (sin las muy comunes), no por significado.
     *
     * @return Collection<int, Incidente>
     */
    public function ejecutar(User $actor, Incidente $incidente): Collection
    {
        $terminos = TerminosDeBusqueda::desde(
            "$incidente->titulo $incidente->descripcion",
            (int) config('seguridad.busqueda.min_token'),
            (int) config('seguridad.busqueda.max_terminos'),
            sinPalabrasVacias: true,
        );

        if ($terminos === []) {
            return new Collection;
        }

        $busqueda = implode(' ', $terminos);

        return Incidente::query()
            ->without(['creador', 'asignado'])
            ->visiblePara(IncidentePolicy::alcanceDe($actor))
            ->whereIn('estado', [Estado::Resuelto->value, Estado::Cerrado->value])
            ->where('categoria', $incidente->categoria->value)
            ->whereKeyNot($incidente->id)
            ->whereFullText(['titulo', 'descripcion'], $busqueda)
            // La misma expresión del filtro, para ordenar por relevancia (MySQL la calcula una sola vez).
            ->orderByRaw('match (titulo, descripcion) against (? in natural language mode) desc', [$busqueda])
            ->orderByDesc('id')
            ->limit(self::MAXIMO_DE_RESULTADOS)
            ->get();
    }
}
