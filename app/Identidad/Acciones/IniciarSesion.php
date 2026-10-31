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
    // Hash argon2id con los parámetros de producción que no pertenece a ninguna cuenta. Se compara cuando
    // el correo no existe, para que el tiempo de respuesta no revele qué cuentas existen.
    private const HASH_FICTICIO = '$argon2id$v=19$m=65536,t=4,p=1$bC84Mmp5UGxhOWFlYkc1SQ$bXghOkbPgtx6RBuHH63EH9Gjj9NWvOvuDWJjSNIDe3k';

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

            $token = $usuario->createToken('api', ['*'], $ahora->copy()->addMinutes((int) config('sanctum.expiration')));
            $this->cerrarSesionesSobrantes($usuario);

            return [$token, $usuario->id];
        });

        if ($token === null) {
            // Fuera de la transacción: el registro debe conservarse. No se guarda lo que se escribió.
            $this->registrarAuditoria->ejecutar(AccionAuditoria::LoginFallido, null, $usuarioId, null, $ip);

            throw ValidationException::withMessages(['email' => [trans('auth.failed')]]);
        }

        return $token;
    }

    // Quedan las sesiones más recientes (incluida la nueva); las más antiguas se cierran.
    private function cerrarSesionesSobrantes(User $usuario): void
    {
        $sobrantes = $usuario->tokens()->orderByDesc('id')->skip((int) config('seguridad.sesiones_maximas'))->take(PHP_INT_MAX)->pluck('id');

        if ($sobrantes->isNotEmpty()) {
            $usuario->tokens()->whereIn('id', $sobrantes)->delete();
        }
    }
}
