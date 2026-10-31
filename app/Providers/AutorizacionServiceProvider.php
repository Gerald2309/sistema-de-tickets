<?php

namespace App\Providers;

use App\Identidad\Dominio\ClavePermiso;
use App\Identidad\Models\User;
use App\Incidentes\Models\Incidente;
use App\Incidentes\Policies\IncidentePolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AutorizacionServiceProvider extends ServiceProvider
{
    // Un Gate por cada permiso del catálogo: añadir un permiso al enum basta para que exista.
    public function boot(): void
    {
        Gate::policy(Incidente::class, IncidentePolicy::class);

        // Para las rutas que solo exigen haber iniciado sesión.
        Gate::define('autenticado', fn (User $usuario) => true);

        // Ver roles y permisos lo necesita quien administra roles y quien asigna roles a usuarios.
        Gate::define('consultar_roles', fn (User $usuario) => $usuario->permisos()->alguno(ClavePermiso::AdministrarRoles, ClavePermiso::AdministrarUsuarios));

        foreach (ClavePermiso::cases() as $permiso) {
            Gate::define($permiso->value, fn (User $usuario) => $usuario->permisos()->tiene($permiso));
        }
    }
}
