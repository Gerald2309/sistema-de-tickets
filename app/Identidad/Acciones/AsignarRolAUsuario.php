<?php

namespace App\Identidad\Acciones;

use App\Auditoria\Acciones\RegistrarAuditoria;
use App\Auditoria\Dominio\AccionAuditoria;
use App\Identidad\Dominio\Excepciones\UltimoAdministrador;
use App\Identidad\Models\Rol;
use App\Identidad\Models\User;
use Illuminate\Support\Facades\DB;

class AsignarRolAUsuario
{
    public function __construct(
        private readonly BloqueoAdministracion $bloqueo,
        private readonly RegistrarAuditoria $registrarAuditoria,
    ) {}

    /**
     * @throws UltimoAdministrador
     */
    public function ejecutar(User $administrador, User $usuario, int $rolId, ?string $ip): User
    {
        return DB::transaction(function () use ($administrador, $usuario, $rolId, $ip) {
            $this->bloqueo->adquirir();

            $usuario = User::findOrFail($usuario->id);
            if ($usuario->rol_id !== $rolId) {
                $usuario->update(['rol_id' => $rolId]);
                $this->bloqueo->exigirQueQuedenAdministradores();

                $nombreDelRol = Rol::findOrFail($rolId)->nombre;
                $this->registrarAuditoria->ejecutar(AccionAuditoria::RolAsignado, $administrador->id, $usuario->id, "Rol «{$nombreDelRol}»", $ip);
            }

            return $usuario->load(['rol', 'cliente']);
        });
    }
}
