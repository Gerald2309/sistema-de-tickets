<?php

namespace App\Identidad\Acciones;

use App\Identidad\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Laravel\Sanctum\PersonalAccessToken;

// Las sesiones abiertas de una persona en otros equipos. La actual se cierra con CerrarSesion.
class CerrarSesionesPropias
{
    /**
     * @throws ModelNotFoundException si no es una sesión de esa persona o es la actual
     */
    public function una(User $usuario, int $sesionId): void
    {
        $cerradas = $this->otras($usuario)->whereKey($sesionId)->delete();

        if ($cerradas === 0) {
            throw new ModelNotFoundException;
        }
    }

    public function lasDemas(User $usuario): void
    {
        $this->otras($usuario)->delete();
    }

    /**
     * @return MorphMany<PersonalAccessToken, User>
     */
    private function otras(User $usuario): MorphMany
    {
        return $usuario->tokens()->whereKeyNot($usuario->currentAccessToken()->id);
    }
}
