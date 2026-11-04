<?php

namespace App\Identidad\Dominio;

use App\Identidad\Dominio\Excepciones\RolFueraDeAlcance;
use App\Identidad\Dominio\Excepciones\UltimoAdministrador;

// Regla: siempre hay quien administre roles y quien administre usuarios, o el sistema queda sin gobierno.
final class Administracion
{
    /**
     * @throws UltimoAdministrador si alguno de los dos conteos es cero.
     */
    public static function exigirQueQuede(int $activosQueAdministranRoles, int $activosQueAdministranUsuarios): void
    {
        if ($activosQueAdministranRoles < 1 || $activosQueAdministranUsuarios < 1) {
            throw new UltimoAdministrador;
        }
    }

    // Los permisos que gobiernan el sistema, frente a los de trabajar con incidentes.
    private const PERMISOS_DE_ADMINISTRACION = [
        ClavePermiso::AdministrarRoles,
        ClavePermiso::AdministrarUsuarios,
        ClavePermiso::VerAuditoria,
        ClavePermiso::AdministrarAutomatizaciones,
    ];

    /**
     * Quien administra usuarios no puede asignar, ni asignarse, un rol con permisos de administración que él
     * no tiene (por ejemplo, el de Administrador). Quien administra roles sí, porque ya decide qué permisos
     * tiene cada rol.
     *
     * @throws RolFueraDeAlcance
     */
    public static function exigirQuePuedaAsignar(ConjuntoPermisos $quienAsigna, ConjuntoPermisos $rol): void
    {
        if ($quienAsigna->tiene(ClavePermiso::AdministrarRoles)) {
            return;
        }

        foreach (self::PERMISOS_DE_ADMINISTRACION as $permiso) {
            if ($rol->tiene($permiso) && ! $quienAsigna->tiene($permiso)) {
                throw new RolFueraDeAlcance;
            }
        }
    }
}
