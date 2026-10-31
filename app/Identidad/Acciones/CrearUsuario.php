<?php

namespace App\Identidad\Acciones;

use App\Auditoria\Acciones\RegistrarAuditoria;
use App\Auditoria\Dominio\AccionAuditoria;
use App\Identidad\Models\User;
use Illuminate\Support\Facades\DB;

class CrearUsuario
{
    public function __construct(private readonly RegistrarAuditoria $registrarAuditoria) {}

    /**
     * @param  array{name: string, email: string, password: string, rol_id: int}  $datos
     */
    public function ejecutar(User $administrador, array $datos, ?string $ip): User
    {
        return DB::transaction(function () use ($administrador, $datos, $ip) {
            $usuario = User::create($datos)->refresh()->load('rol');

            $this->registrarAuditoria->ejecutar(AccionAuditoria::UsuarioCreado, $administrador->id, $usuario->id, "Rol «{$usuario->rol->nombre}»", $ip);

            return $usuario;
        });
    }
}
