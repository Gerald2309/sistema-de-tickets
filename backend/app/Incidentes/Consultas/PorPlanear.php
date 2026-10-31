<?php

namespace App\Incidentes\Consultas;

use App\Identidad\Models\User;
use App\Incidentes\Dominio\CodigoIncidente;
use App\Incidentes\Dominio\Estado;
use App\Incidentes\Dominio\Vencimiento;
use App\Incidentes\Models\Incidente;
use App\Incidentes\Policies\IncidentePolicy;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Pagination\CursorPaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Query\Builder as QueryBuilder;

class PorPlanear
{
    public const POR_PAGINA = 20;

    public const MAXIMO_PENDIENTES = 10;

    /**
     * Lo asignado a esa persona, sin resolver y que nunca puso en su agenda, lo que vence antes primero. Por
     * tramos: quien tiene muchos asignados los recorre buscando, no en una lista sin fin.
     *
     * @return CursorPaginator<int, Incidente>
     */
    public function ejecutar(User $usuario, ?string $buscar): CursorPaginator
    {
        return $this->sinResolverDe($usuario)
            ->whereNotExists(fn (QueryBuilder $plan) => $this->entradasDe($plan, $usuario))
            ->when($buscar, fn (Builder $consulta, string $texto) => $this->buscar($consulta, $texto))
            ->orderBy('fecha_vencimiento')
            ->orderBy('id')
            ->cursorPaginate(self::POR_PAGINA);
    }

    /**
     * Los primeros que «Sugerir mi día» puede poner: los mismos de la bandeja, sin los que la persona marcó
     * como bloqueados.
     *
     * @return Collection<int, Incidente>
     */
    public function sugeribles(User $usuario, int $cantidad): Collection
    {
        return $this->sinResolverDe($usuario)
            ->whereNotExists(fn (QueryBuilder $plan) => $this->entradasDe($plan, $usuario))
            ->whereNotExists(fn (QueryBuilder $bloqueos) => $bloqueos->from('bloqueos_personales')
                ->whereColumn('bloqueos_personales.incidente_id', 'incidentes.id')
                ->where('bloqueos_personales.usuario_id', $usuario->id))
            ->orderBy('fecha_vencimiento')
            ->orderByDesc('prioridad')
            ->orderBy('id')
            ->limit($cantidad)
            ->get();
    }

    /**
     * Lo que planeó algún día anterior, sigue sin resolver y no volvió a poner desde hoy: quedó pendiente.
     *
     * @return Collection<int, Incidente>
     */
    public function pendientes(User $usuario, CarbonImmutable $hoy): Collection
    {
        return $this->sinResolverDe($usuario)
            ->whereExists(fn (QueryBuilder $plan) => $this->entradasDe($plan, $usuario)->where('fecha', '<', $hoy->toDateString()))
            ->whereNotExists(fn (QueryBuilder $plan) => $this->entradasDe($plan, $usuario)->where('fecha', '>=', $hoy->toDateString()))
            ->orderBy('fecha_vencimiento')
            ->limit(self::MAXIMO_PENDIENTES)
            ->get();
    }

    /**
     * @return Builder<Incidente>
     */
    private function sinResolverDe(User $usuario): Builder
    {
        return Incidente::query()
            ->paraElPlanDe($usuario->id)
            ->visiblePara(IncidentePolicy::alcanceDe($usuario))
            ->where('usuario_asignado_id', $usuario->id)
            ->whereIn('estado', array_map(fn (Estado $estado) => $estado->value, Vencimiento::estadosQueVencen()));
    }

    private function entradasDe(QueryBuilder $plan, User $usuario): QueryBuilder
    {
        return $plan->from('entradas_del_plan')
            ->whereColumn('entradas_del_plan.incidente_id', 'incidentes.id')
            ->where('entradas_del_plan.usuario_id', $usuario->id);
    }

    /**
     * Por código exacto o por parte del título. Son pocos (los asignados a una persona): basta un LIKE.
     *
     * @param  Builder<Incidente>  $consulta
     */
    private function buscar(Builder $consulta, string $texto): void
    {
        $id = CodigoIncidente::aId($texto);
        if ($id !== null) {
            $consulta->whereKey($id);

            return;
        }

        $consulta->where('titulo', 'like', '%'.addcslashes($texto, '%_\\').'%');
    }
}
