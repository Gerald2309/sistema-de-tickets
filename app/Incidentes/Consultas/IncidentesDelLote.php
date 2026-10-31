<?php

namespace App\Incidentes\Consultas;

use App\Identidad\Models\User;
use App\Incidentes\Models\Incidente;
use App\Incidentes\Policies\IncidentePolicy;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class IncidentesDelLote
{
    public const MAXIMO = 100;

    /**
     * Los incidentes pedidos, de menor a mayor id (siempre en el mismo orden, para no bloquearse entre lotes).
     * Si alguno no existe o la persona no puede verlo, el lote entero se rechaza como "no encontrado",
     * sin revelar cuál fue.
     *
     * @param  list<int>  $ids
     * @return Collection<int, Incidente>
     *
     * @throws ModelNotFoundException
     */
    public function ejecutar(User $actor, array $ids): Collection
    {
        $ids = array_values(array_unique($ids));

        $incidentes = Incidente::query()
            ->visiblePara(IncidentePolicy::alcanceDe($actor))
            ->whereIn('id', $ids)
            ->orderBy('id')
            ->get();

        if ($incidentes->count() !== count($ids)) {
            throw (new ModelNotFoundException)->setModel(Incidente::class, array_values(array_diff($ids, $incidentes->modelKeys())));
        }

        return $incidentes;
    }
}
