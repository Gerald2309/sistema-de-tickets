<?php

namespace App\Identidad\Acciones;

use App\Auditoria\Acciones\RegistrarAuditoria;
use App\Auditoria\Dominio\AccionAuditoria;
use App\Identidad\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\NewAccessToken;

class IniciarSesion
{
    // Hash de una contraseña aleatoria que nadie conoce. Se compara cuando el correo no existe,
    // para que el tiempo de respuesta no revele qué cuentas existen.
    private const HASH_FICTICIO = '$2y$12$vSE5BQaiZoljsApLwByuMe.5IuYZzqXOuEK1Pv/uYJu1./UWqrdYi';

    public function __construct(private readonly RegistrarAuditoria $registrarAuditoria) {}

    /**
     * @throws ValidationException con el mismo mensaje para cualquier fallo, sin revelar la causa.
     */
    public function ejecutar(string $email, string $password, ?string $ip, Carbon $ahora): NewAccessToken
    {
        // El usuario se bloquea mientras se decide: un login simultáneo con una desactivación
        // no deja un token vivo para una cuenta inactiva.
        [$token, $usuarioId] = DB::transaction(function () use ($email, $password, $ahora) {
            $usuario = User::where('email', $email)->lockForUpdate()->first();
            $coincide = Hash::check($password, $usuario?->password ?? self::HASH_FICTICIO);

            if ($usuario === null || ! $coincide || ! $usuario->activo) {
                return [null, $usuario?->id];
            }

            return [$usuario->createToken('api', ['*'], $ahora->copy()->addMinutes((int) config('sanctum.expiration'))), $usuario->id];
        });

        if ($token === null) {
            // Fuera de la transacción: el registro debe conservarse. No se guarda lo que se escribió.
            $this->registrarAuditoria->ejecutar(AccionAuditoria::LoginFallido, null, $usuarioId, null, $ip);

            throw ValidationException::withMessages(['email' => [trans('auth.failed')]]);
        }

        return $token;
    }
}
