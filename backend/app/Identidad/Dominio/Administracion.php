<?php

namespace App\Identidad\Dominio;

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
}
