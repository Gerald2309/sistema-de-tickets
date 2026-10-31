<?php

namespace App\Identidad\Consultas;

use App\Identidad\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ListarUsuarios
{
    public const POR_PAGINA_MAXIMO = 100;

    public const POR_PAGINA_PREDETERMINADO = 15;

    /**
     * @param  array{q?: string|null, rol_id?: int|null, cliente_id?: int|null, activo?: bool|null}  $filtros
     * @return LengthAwarePaginator<int, User>
     */
    public function ejecutar(array $filtros, int $porPagina): LengthAwarePaginator
    {
        $activo = $filtros['activo'] ?? null;

        return User::query()
            ->with(['rol', 'cliente'])
            ->when($filtros['q'] ?? null, function ($consulta, string $texto) {
                $patron = '%'.addcslashes($texto, '%_\\').'%';

                $consulta->where(fn ($donde) => $donde->where('name', 'like', $patron)->orWhere('email', 'like', $patron));
            })
            ->when($filtros['rol_id'] ?? null, fn ($consulta, int $rolId) => $consulta->where('rol_id', $rolId))
            ->when($filtros['cliente_id'] ?? null, fn ($consulta, int $clienteId) => $consulta->where('cliente_id', $clienteId))
            ->when($activo !== null, fn ($consulta) => $consulta->where('activo', $activo))
            ->orderBy('name')
            ->orderBy('id')
            ->paginate($porPagina);
    }
}
