<?php

namespace App\Identidad\Acciones;

use App\Auditoria\Acciones\RegistrarAuditoria;
use App\Auditoria\Dominio\AccionAuditoria;
use App\Identidad\Models\Rol;
use App\Identidad\Models\User;
use Illuminate\Support\Facades\DB;

class CrearRol
{
    public function __construct(private readonly RegistrarAuditoria $registrarAuditoria) {}

    // El rol nace sin permisos: se le asignan aparte.
    public function ejecutar(User $administrador, string $nombre, ?string $ip): Rol
    {
        return DB::transaction(function () use ($administrador, $nombre, $ip) {
            $rol = Rol::create(['nombre' => $nombre])->load('permisos')->loadCount('usuarios');

            $this->registrarAuditoria->ejecutar(AccionAuditoria::RolCreado, $administrador->id, null, "Rol «{$nombre}»", $ip);

            return $rol;
        });
    }
}
