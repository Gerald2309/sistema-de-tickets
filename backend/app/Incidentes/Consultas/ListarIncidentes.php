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
use DateTimeInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class ListarIncidentes
{
    // El listado no lleva los textos largos (descripción y solución, hasta 10 000 caracteres cada uno): ninguna
    // lista los muestra y el detalle los trae. La exportación sí los necesita y usa la consulta completa.
    private const COLUMNAS_DEL_LISTADO = [
        'incidentes.id', 'titulo', 'prioridad', 'estado', 'categoria', 'calificacion', 'usuario_creador_id',
        'usuario_asignado_id', 'cliente_id', 'fecha_vencimiento', 'incidentes.created_at', 'incidentes.updated_at',
    ];

    /**
     * @return LengthAwarePaginator<int, Incidente>
     */
    public function ejecutar(User $actor, FiltrosDeIncidentes $filtros): LengthAwarePaginator
    {
        return $this->consulta($actor, $filtros)
            ->select(self::COLUMNAS_DEL_LISTADO)
            ->withCount(Incidente::conteoDeMensajesSinLeer($actor->id, IncidentePolicy::alcanceDe($actor)))
            ->paginate($filtros->porPagina)
            ->withQueryString();
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
            ->when($filtros->clienteId, fn (Builder $consulta) => $consulta->where('cliente_id', $filtros->clienteId))
            ->when($filtros->porVencer, fn (Builder $consulta, VentanaDeVencimiento $ventana) => $consulta
                ->whereIn('estado', array_map(fn (Estado $estado) => $estado->value, Vencimiento::estadosQueVencen()))
                ->whereBetween('fecha_vencimiento', [$ventana->desde, $ventana->hasta]))
            ->when($filtros->vencidosAl, fn (Builder $consulta, DateTimeInterface $instante) => $consulta->vencidosAl($instante))
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
