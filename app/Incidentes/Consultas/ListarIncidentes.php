<?php

namespace App\Incidentes\Consultas;

use App\Identidad\Models\User;
use App\Incidentes\Dominio\CampoOrden;
use App\Incidentes\Dominio\FiltrosDeIncidentes;
use App\Incidentes\Models\Incidente;
use App\Incidentes\Policies\IncidentePolicy;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class ListarIncidentes
{
    /**
     * Una sola consulta con todos los filtros combinados. Solo devuelve lo que el usuario puede ver,
     * sin los incidentes borrados. El orden se desempata por id, en el mismo sentido.
     *
     * @return LengthAwarePaginator<int, Incidente>
     */
    public function ejecutar(User $actor, FiltrosDeIncidentes $filtros): LengthAwarePaginator
    {
        $columnaDeFecha = $filtros->campoFecha->columna();
        $sentido = $filtros->direccion->value;

        return Incidente::query()
            ->visiblePara(IncidentePolicy::alcanceDe($actor))
            ->when($filtros->terminos !== [], fn (Builder $consulta) => $consulta->whereFullText(
                ['titulo', 'descripcion'],
                $this->expresionBooleana($filtros->terminos),
                ['mode' => 'boolean'],
            ))
            ->when($filtros->prioridad, fn (Builder $consulta) => $consulta->where('prioridad', $filtros->prioridad->value))
            ->when($filtros->estado, fn (Builder $consulta) => $consulta->where('estado', $filtros->estado->value))
            ->when($filtros->categoria, fn (Builder $consulta) => $consulta->where('categoria', $filtros->categoria->value))
            ->when($filtros->usuarioAsignadoId, fn (Builder $consulta) => $consulta->where('usuario_asignado_id', $filtros->usuarioAsignadoId))
            ->when($filtros->fechaDesde, fn (Builder $consulta) => $consulta->where($columnaDeFecha, '>=', $filtros->fechaDesde))
            ->when($filtros->fechaHasta, fn (Builder $consulta) => $consulta->where($columnaDeFecha, '<=', $filtros->fechaHasta))
            ->orderBy($filtros->ordenarPor->columna(), $sentido)
            ->when($filtros->ordenarPor !== CampoOrden::Id, fn (Builder $consulta) => $consulta->orderBy('id', $sentido))
            ->paginate($filtros->porPagina)
            ->withQueryString();
    }

    /**
     * Cada palabra es obligatoria (`+palabra`): la búsqueda devuelve los incidentes que las contienen todas.
     *
     * @param  list<string>  $terminos
     */
    private function expresionBooleana(array $terminos): string
    {
        return implode(' ', array_map(fn (string $termino) => "+$termino", $terminos));
    }
}
