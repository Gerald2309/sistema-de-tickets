<?php

namespace App\Identidad\Acciones;

use App\Auditoria\Acciones\RegistrarAuditoria;
use App\Auditoria\Dominio\AccionAuditoria;
use App\Identidad\Dominio\Excepciones\UltimoAdministrador;
use App\Identidad\Models\User;
use Illuminate\Support\Facades\DB;

class ModificarUsuario
{
    public function __construct(
        private readonly BloqueoAdministracion $bloqueo,
        private readonly RegistrarAuditoria $registrarAuditoria,
    ) {}

    /**
     * Modifica nombre, correo, empresa, estado activo o, para restablecerla, la contraseña. Desactivar a alguien
     * o restablecerle la contraseña cierra sus sesiones, y con una contraseña restablecida debe cambiarla al entrar.
     *
     * @param  array{name?: string, email?: string, activo?: bool, password?: string, cliente_id?: int|null}  $cambios
     *
     * @throws UltimoAdministrador
     */
    public function ejecutar(User $administrador, User $usuario, array $cambios, ?string $ip): User
    {
        return DB::transaction(function () use ($administrador, $usuario, $cambios, $ip) {
            // Solo el estado activo puede dejar al sistema sin administradores.
            if (array_key_exists('activo', $cambios)) {
                $this->bloqueo->adquirir();
            }

            // Una contraseña restablecida la conoce el administrador: la persona debe cambiarla al entrar.
            if (array_key_exists('password', $cambios)) {
                $cambios['debe_cambiar_contrasena'] = true;
            }

            $usuario = User::findOrFail($usuario->id)->fill($cambios);
            $cambiados = array_keys($usuario->getDirty());
            $usuario->save();

            $desactivado = in_array('activo', $cambiados, true) && ! $usuario->activo;
            if ($desactivado) {
                $this->bloqueo->exigirQueQuedenAdministradores();
            }

            $this->auditar($administrador, $usuario, $cambiados, $ip);

            if ($desactivado || in_array('password', $cambiados, true)) {
                $usuario->tokens()->delete();
            }

            return $usuario->load(['rol', 'cliente']);
        });
    }

    /**
     * @param  list<string>  $cambiados
     */
    private function auditar(User $administrador, User $usuario, array $cambiados, ?string $ip): void
    {
        $registrar = fn (AccionAuditoria $accion, ?string $detalle = null) => $this->registrarAuditoria->ejecutar($accion, $administrador->id, $usuario->id, $detalle, $ip);

        $datos = array_values(array_intersect($cambiados, ['name', 'email', 'cliente_id']));
        if ($datos !== []) {
            $registrar(AccionAuditoria::UsuarioModificado, 'Campos: '.implode(', ', $datos));
        }
        if (in_array('activo', $cambiados, true)) {
            $registrar($usuario->activo ? AccionAuditoria::UsuarioReactivado : AccionAuditoria::UsuarioDesactivado);
        }
        if (in_array('password', $cambiados, true)) {
            $registrar(AccionAuditoria::ContrasenaRestablecida);
        }
    }
}
