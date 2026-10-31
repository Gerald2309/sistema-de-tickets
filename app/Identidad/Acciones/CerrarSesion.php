<?php

namespace App\Identidad\Acciones;

use App\Identidad\Models\User;

class CerrarSesion
{
    // Revoca solo el token con el que se hizo la petición.
    public function ejecutar(User $usuario): void
    {
        $usuario->currentAccessToken()->delete();
    }
}
