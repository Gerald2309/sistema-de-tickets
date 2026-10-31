<?php

namespace App\Auditoria\Consultas;

use App\Auditoria\Dominio\AccionAuditoria;
use App\Auditoria\Models\Auditoria;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;

class ListarAuditoria
{
    public const POR_PAGINA_MAXIMO = 100;

    public const POR_PAGINA_PREDETERMINADO = 15;

    /**
     * Lo más reciente primero. Los nombres se leen con un join sobre la tabla `users` (el módulo no
     * usa los modelos de Identidad) y son los actuales, no los de cuando ocurrió la acción.
     *
     * @param  array{accion?: AccionAuditoria|null, usuario_id?: int|null, usuario_afectado_id?: int|null, desde?: Carbon|null, hasta?: Carbon|null}  $filtros
     * @return LengthAwarePaginator<int, Auditoria>
     */
    public function ejecutar(array $filtros, int $porPagina): LengthAwarePaginator
    {
        return Auditoria::query()
            ->leftJoin('users as actor', 'actor.id', '=', 'auditoria.usuario_id')
            ->leftJoin('users as afectado', 'afectado.id', '=', 'auditoria.usuario_afectado_id')
            ->select('auditoria.*', 'actor.name as usuario_nombre', 'afectado.name as usuario_afectado_nombre')
            ->when($filtros['accion'] ?? null, fn ($consulta, AccionAuditoria $accion) => $consulta->where('auditoria.accion', $accion->value))
            ->when($filtros['usuario_id'] ?? null, fn ($consulta, int $id) => $consulta->where('auditoria.usuario_id', $id))
            ->when($filtros['usuario_afectado_id'] ?? null, fn ($consulta, int $id) => $consulta->where('auditoria.usuario_afectado_id', $id))
            ->when($filtros['desde'] ?? null, fn ($consulta, Carbon $desde) => $consulta->where('auditoria.created_at', '>=', $desde))
            ->when($filtros['hasta'] ?? null, fn ($consulta, Carbon $hasta) => $consulta->where('auditoria.created_at', '<=', $hasta))
            ->orderByDesc('auditoria.created_at')
            ->orderByDesc('auditoria.id')
            ->paginate($porPagina);
    }
}
