<?php

namespace App\Identidad\Acciones;

use App\Identidad\Consultas\ContarAdministradoresActivos;
use App\Identidad\Dominio\Administracion;
use App\Identidad\Dominio\ClavePermiso;
use App\Identidad\Dominio\Excepciones\UltimoAdministrador;
use App\Identidad\Models\Rol;

// Las operaciones que pueden quitar administradores (desactivar, cambiar de rol, quitar permisos a un rol)
// se hacen de una en una: todas bloquean las mismas filas, las de `roles`, en el mismo orden. Así la
// segunda ve el resultado de la primera y la regla del último administrador no se salta por simultaneidad.
// Se llama al empezar la transacción, antes de leer nada, para no leer datos viejos.
class BloqueoAdministracion
{
    public function __construct(private readonly ContarAdministradoresActivos $contarAdministradoresActivos) {}

    public function adquirir(): void
    {
        Rol::query()->orderBy('id')->lockForUpdate()->pluck('id');
    }

    /**
     * @throws UltimoAdministrador
     */
    public function exigirQueQuedenAdministradores(): void
    {
        Administracion::exigirQueQuede(
            $this->contarAdministradoresActivos->ejecutar(ClavePermiso::AdministrarRoles),
            $this->contarAdministradoresActivos->ejecutar(ClavePermiso::AdministrarUsuarios),
        );
    }
}
