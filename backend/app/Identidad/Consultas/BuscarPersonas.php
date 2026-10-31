<?php

namespace App\Identidad\Consultas;

use App\Identidad\Models\User;
use Illuminate\Support\Collection;

// Personas para elegir en un selector: asignar un incidente o filtrar por persona. Devuelve solo lo mínimo
// para esa tarea (id, nombre y si está activa) y como mucho MAXIMO coincidencias, sin paginar.
class BuscarPersonas
{
    public const MAXIMO = 20;

    /**
     * @param  list<int>  $ids  personas concretas, por ejemplo las de un filtro ya elegido. Si se piden, se ignora
     *                          el texto y se devuelven aunque estén inactivas: el filtro puede ser de un incidente viejo.
     * @return Collection<int, User>
     */
    public function ejecutar(?string $texto, array $ids, bool $incluirInactivas): Collection
    {
        $porIds = $ids !== [];

        return User::query()
            ->select(['id', 'name', 'activo'])
            ->when($porIds, fn ($consulta) => $consulta->whereIn('id', $ids))
            ->when(! $porIds && $texto !== null, fn ($consulta) => $consulta->where('name', 'like', '%'.addcslashes((string) $texto, '%_\\').'%'))
            ->when(! $porIds && ! $incluirInactivas, fn ($consulta) => $consulta->where('activo', true))
            ->orderBy('name')
            ->orderBy('id')
            ->limit(self::MAXIMO)
            ->get();
    }
}
