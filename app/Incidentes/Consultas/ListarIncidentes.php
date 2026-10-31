<?php

namespace App\Incidentes\Consultas;

use App\Identidad\Models\User;
use App\Incidentes\Dominio\CampoOrden;
use App\Incidentes\Dominio\Categoria;
use App\Incidentes\Dominio\Estado;
use App\Incidentes\Dominio\FiltrosDeIncidentes;
use App\Incidentes\Dominio\Prioridad;
use App\Incidentes\Dominio\Vencimiento;
use App\Incidentes\Dominio\VentanaDeVencimiento;
use App\Incidentes\Models\Incidente;
use App\Incidentes\Policies\IncidentePolicy;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class ListarIncidentes
{
    /**
     * @return LengthAwarePaginator<int, Incidente>
     */
    public function ejecutar(User $actor, FiltrosDeIncidentes $filtros): LengthAwarePaginator
    {
        return $this->consulta($actor, $filtros)->paginate($filtros->porPagina)->withQueryString();
    }

    /**
     * Una sola consulta con todos los filtros combinados. Solo devuelve lo que el usuario puede ver,
     * sin los incidentes borrados. El orden se desempata por id, en el mismo sentido. La usan el listado
     * y la exportación: así ven exactamente lo mismo.
     *
     * @return Builder<Incidente>
     */
    public function consulta(User $actor, FiltrosDeIncidentes $filtros): Builder
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
            ->when($filtros->prioridad, fn (Builder $consulta, Prioridad $prioridad) => $consulta->where('prioridad', $prioridad->value))
            ->when($filtros->estado, fn (Builder $consulta, Estado $estado) => $consulta->where('estado', $estado->value))
            ->when($filtros->categoria, fn (Builder $consulta, Categoria $categoria) => $consulta->where('categoria', $categoria->value))
            ->when($filtros->usuarioAsignadoId, fn (Builder $consulta) => $consulta->where('usuario_asignado_id', $filtros->usuarioAsignadoId))
            ->when($filtros->usuarioCreadorId, fn (Builder $consulta) => $consulta->where('usuario_creador_id', $filtros->usuarioCreadorId))
            ->when($filtros->porVencer, fn (Builder $consulta, VentanaDeVencimiento $ventana) => $consulta
                ->whereIn('estado', array_map(fn (Estado $estado) => $estado->value, Vencimiento::estadosQueVencen()))
                ->whereBetween('fecha_vencimiento', [$ventana->desde, $ventana->hasta]))
            ->when($filtros->fechaDesde, fn (Builder $consulta) => $consulta->where($columnaDeFecha, '>=', $filtros->fechaDesde))
            ->when($filtros->fechaHasta, fn (Builder $consulta) => $consulta->where($columnaDeFecha, '<=', $filtros->fechaHasta))
            ->orderBy($filtros->ordenarPor->columna(), $sentido)
            ->when($filtros->ordenarPor !== CampoOrden::Id, fn (Builder $consulta) => $consulta->orderBy('id', $sentido));
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
