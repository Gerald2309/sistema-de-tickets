<?php

namespace App\Identidad\Acciones;

use App\Auditoria\Acciones\RegistrarAuditoria;
use App\Auditoria\Dominio\AccionAuditoria;
use App\Identidad\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class CambiarContrasena
{
    public function __construct(
        private readonly RegistrarAuditoria $registrarAuditoria,
        private readonly CerrarSesionesPropias $cerrarSesiones,
    ) {}

    /**
     * Cambia la contraseña propia y cierra el resto de sesiones del usuario (no la actual). Con ella cumple el
     * cambio que se le exige tras recibir una contraseña de un administrador.
     *
     * @throws ValidationException si la contraseña actual no coincide.
     */
    public function ejecutar(User $usuario, string $actual, string $nueva, ?string $ip): void
    {
        if (! Hash::check($actual, $usuario->password)) {
            throw ValidationException::withMessages(['password_actual' => [trans('auth.password')]]);
        }

        DB::transaction(function () use ($usuario, $nueva, $ip) {
            $usuario->update(['password' => $nueva, 'debe_cambiar_contrasena' => false]);
            $this->cerrarSesiones->lasDemas($usuario);

            $this->registrarAuditoria->ejecutar(AccionAuditoria::ContrasenaCambiada, $usuario->id, $usuario->id, null, $ip);
        });
    }
}
