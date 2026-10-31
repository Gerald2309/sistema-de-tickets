<?php

namespace App\Identidad\Acciones;

use App\Auditoria\Acciones\RegistrarAuditoria;
use App\Auditoria\Dominio\AccionAuditoria;
use App\Identidad\Dominio\ClavePermiso;
use App\Identidad\Dominio\Excepciones\UltimoAdministrador;
use App\Identidad\Models\Permiso;
use App\Identidad\Models\Rol;
use App\Identidad\Models\User;
use Illuminate\Support\Facades\DB;

class AsignarPermisosARol
{
    public function __construct(
        private readonly BloqueoAdministracion $bloqueo,
        private readonly RegistrarAuditoria $registrarAuditoria,
    ) {}

    /**
     * Fija el conjunto exacto de permisos del rol y audita cada permiso concedido o retirado.
     *
     * @param  list<ClavePermiso>  $claves
     *
     * @throws UltimoAdministrador
     */
    public function ejecutar(User $administrador, Rol $rol, array $claves, ?string $ip): Rol
    {
        return DB::transaction(function () use ($administrador, $rol, $claves, $ip) {
            $this->bloqueo->adquirir();

            $rol = Rol::with('permisos')->findOrFail($rol->id);
            $actuales = $rol->permisos->map(fn (Permiso $permiso) => $permiso->clave->value)->all();
            $nuevas = array_map(fn (ClavePermiso $clave) => $clave->value, $claves);

            $rol->permisos()->sync(Permiso::whereIn('clave', $nuevas)->pluck('id'));

            $retirados = array_values(array_diff($actuales, $nuevas));
            if ($retirados !== []) {
                $this->bloqueo->exigirQueQuedenAdministradores();
            }

            $this->auditar($administrador, AccionAuditoria::PermisoConcedido, $rol, array_diff($nuevas, $actuales), $ip);
            $this->auditar($administrador, AccionAuditoria::PermisoRetirado, $rol, $retirados, $ip);

            return $rol->load('permisos')->loadCount('usuarios');
        });
    }

    /**
     * @param  array<int, string>  $claves
     */
    private function auditar(User $administrador, AccionAuditoria $accion, Rol $rol, array $claves, ?string $ip): void
    {
        foreach ($claves as $clave) {
            $this->registrarAuditoria->ejecutar($accion, $administrador->id, null, "Rol «{$rol->nombre}»: {$clave}", $ip);
        }
    }
}
