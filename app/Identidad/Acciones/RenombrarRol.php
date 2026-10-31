<?php

namespace App\Identidad\Acciones;

use App\Auditoria\Acciones\RegistrarAuditoria;
use App\Auditoria\Dominio\AccionAuditoria;
use App\Identidad\Models\Rol;
use App\Identidad\Models\User;
use Illuminate\Support\Facades\DB;

class RenombrarRol
{
    public function __construct(private readonly RegistrarAuditoria $registrarAuditoria) {}

    public function ejecutar(User $administrador, Rol $rol, string $nombre, ?string $ip): Rol
    {
        return DB::transaction(function () use ($administrador, $rol, $nombre, $ip) {
            $anterior = $rol->nombre;
            $rol->update(['nombre' => $nombre]);

            if ($rol->wasChanged('nombre')) {
                $this->registrarAuditoria->ejecutar(AccionAuditoria::RolRenombrado, $administrador->id, null, "«{$anterior}» pasó a «{$nombre}»", $ip);
            }

            return $rol->load('permisos')->loadCount('usuarios');
        });
    }
}
