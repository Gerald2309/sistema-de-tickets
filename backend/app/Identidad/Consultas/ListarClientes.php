<?php

namespace App\Identidad\Consultas;

use App\Identidad\Models\Cliente;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ListarClientes
{
    public const POR_PAGINA_MAXIMO = 100;

    public const POR_PAGINA_PREDETERMINADO = 15;

    /**
     * Ordenadas por nombre, con cuántas personas tiene cada una.
     *
     * @param  array{q?: string|null, activo?: bool|null}  $filtros
     * @return LengthAwarePaginator<int, Cliente>
     */
    public function ejecutar(array $filtros, int $porPagina): LengthAwarePaginator
    {
        $activo = $filtros['activo'] ?? null;

        return Cliente::query()
            ->withCount('usuarios')
            ->when($filtros['q'] ?? null, fn ($consulta, string $texto) => $consulta->where('nombre', 'like', '%'.addcslashes($texto, '%_\\').'%'))
            ->when($activo !== null, fn ($consulta) => $consulta->where('activo', $activo))
            ->orderBy('nombre')
            ->orderBy('id')
            ->paginate($porPagina);
    }
}
