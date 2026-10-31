<?php

namespace App\Providers;

use App\Identidad\Dominio\ClavePermiso;
use App\Identidad\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AutorizacionServiceProvider extends ServiceProvider
{
    // Un Gate por cada permiso del catálogo: añadir un permiso al enum basta para que exista.
    public function boot(): void
    {
        // Para las rutas que solo exigen haber iniciado sesión.
        Gate::define('autenticado', fn (User $usuario) => true);

        foreach (ClavePermiso::cases() as $permiso) {
            Gate::define($permiso->value, fn (User $usuario) => $usuario->permisos()->tiene($permiso));
        }
    }
}
