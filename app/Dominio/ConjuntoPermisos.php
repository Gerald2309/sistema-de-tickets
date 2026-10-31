<?php

namespace App\Dominio;

// Permisos de un usuario. Valor inmutable, sin acceso a la base de datos.
final class ConjuntoPermisos
{
    /** @var array<string, true> */
    private array $claves = [];

    /**
     * @param  iterable<ClavePermiso>  $permisos
     */
    public function __construct(iterable $permisos)
    {
        foreach ($permisos as $permiso) {
            $this->claves[$permiso->value] = true;
        }
    }

    public function tiene(ClavePermiso $permiso): bool
    {
        return isset($this->claves[$permiso->value]);
    }

    public function alguno(ClavePermiso ...$permisos): bool
    {
        foreach ($permisos as $permiso) {
            if ($this->tiene($permiso)) {
                return true;
            }
        }

        return false;
    }
}
