<?php

namespace App\Identidad\Consultas;

use App\Identidad\Dominio\ClavePermiso;
use App\Identidad\Models\User;

class ContarAdministradoresActivos
{
    /**
     * Usuarios activos cuyo rol tiene el permiso. Lee con bloqueo para ver siempre lo último
     * confirmado, no la instantánea de la transacción.
     */
    public function ejecutar(ClavePermiso $permiso): int
    {
        return User::query()
            ->where('activo', true)
            ->whereHas('rol.permisos', fn ($permisos) => $permisos->where('clave', $permiso->value))
            ->lockForUpdate()
            ->count();
    }
}
