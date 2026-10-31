<?php

namespace App\Identidad\Acciones;

use App\Auditoria\Acciones\RegistrarAuditoria;
use App\Auditoria\Dominio\AccionAuditoria;
use App\Identidad\Dominio\Excepciones\RolEnUso;
use App\Identidad\Models\Rol;
use App\Identidad\Models\User;
use Illuminate\Support\Facades\DB;

class EliminarRol
{
    public function __construct(private readonly RegistrarAuditoria $registrarAuditoria) {}

    /**
     * @throws RolEnUso si algún usuario lo tiene asignado.
     */
    public function ejecutar(User $administrador, Rol $rol, ?string $ip): void
    {
        DB::transaction(function () use ($administrador, $rol, $ip) {
            // Con la fila bloqueada nadie puede asignar el rol mientras se comprueba y se borra.
            $rol = Rol::whereKey($rol->id)->lockForUpdate()->firstOrFail();

            if ($rol->usuarios()->exists()) {
                throw new RolEnUso;
            }

            $rol->delete();

            $this->registrarAuditoria->ejecutar(AccionAuditoria::RolEliminado, $administrador->id, null, "Rol «{$rol->nombre}»", $ip);
        });
    }
}
