<?php

namespace App\Identidad\Consultas;

use App\Identidad\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ListarUsuarios
{
    public const POR_PAGINA_MAXIMO = 100;

    public const POR_PAGINA_PREDETERMINADO = 15;

    /**
     * @param  array{q?: string|null, rol_id?: int|null, activo?: bool|null}  $filtros
     * @return LengthAwarePaginator<int, User>
     */
    public function ejecutar(array $filtros, int $porPagina): LengthAwarePaginator
    {
        return User::query()
            ->with('rol')
            ->when($filtros['q'] ?? null, function ($consulta, string $texto) {
                $patron = '%'.addcslashes($texto, '%_\\').'%';

                $consulta->where(fn ($donde) => $donde->where('name', 'like', $patron)->orWhere('email', 'like', $patron));
            })
            ->when($filtros['rol_id'] ?? null, fn ($consulta, int $rolId) => $consulta->where('rol_id', $rolId))
            ->when(isset($filtros['activo']), fn ($consulta) => $consulta->where('activo', $filtros['activo']))
            ->orderBy('name')
            ->orderBy('id')
            ->paginate($porPagina);
    }
}
