<?php

namespace App\Identidad\Consultas;

use App\Identidad\Models\Rol;
use Illuminate\Database\Eloquent\Collection;

class ListarRoles
{
    /**
     * Pocos roles: se devuelven todos, con sus permisos y cuántos usuarios los tienen.
     *
     * @return Collection<int, Rol>
     */
    public function ejecutar(): Collection
    {
        return Rol::query()->with('permisos')->withCount('usuarios')->orderBy('id')->get();
    }
}
