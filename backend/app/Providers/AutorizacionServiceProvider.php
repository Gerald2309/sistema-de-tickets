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

        // Con una contraseña puesta por un administrador, solo lo que pide haber iniciado sesión (su perfil, cambiar
        // la contraseña, cerrar sesiones) hasta que la cambie. Se niega aquí, antes de cualquier permiso o política.
        Gate::before(fn (?User $usuario, string $habilidad) => $usuario?->debe_cambiar_contrasena && $habilidad !== 'autenticado' ? false : null);

        // Para las rutas que solo exigen haber iniciado sesión.
        Gate::define('autenticado', fn (User $usuario) => true);

        // Ver roles y permisos lo necesita quien administra roles, quien asigna roles a usuarios y quien arma
        // automatizaciones (avisar a un rol).
        Gate::define('consultar_roles', fn (User $usuario) => $usuario->permisos()->alguno(
            ClavePermiso::AdministrarRoles,
            ClavePermiso::AdministrarUsuarios,
            ClavePermiso::AdministrarAutomatizaciones,
        ));

        // Las empresas las administra quien administra usuarios; las consultan también quien ve todos los incidentes
        // (filtros) y quien arma automatizaciones (condición por empresa).
        Gate::define('consultar_clientes', fn (User $usuario) => $usuario->permisos()->alguno(
            ClavePermiso::AdministrarUsuarios,
            ClavePermiso::VerTodos,
            ClavePermiso::AdministrarAutomatizaciones,
        ));

        // Elegir a una persona en un selector lo necesita quien asigna incidentes, quien filtra por persona
        // (ve todo o audita), quien administra usuarios y quien arma automatizaciones (asignar, avisar).
        Gate::define('consultar_personas', fn (User $usuario) => $usuario->permisos()->alguno(
            ClavePermiso::Reasignar,
            ClavePermiso::VerTodos,
            ClavePermiso::VerAuditoria,
            ClavePermiso::AdministrarUsuarios,
            ClavePermiso::AdministrarAutomatizaciones,
        ));

        foreach (ClavePermiso::cases() as $permiso) {
            Gate::define($permiso->value, fn (User $usuario) => $usuario->permisos()->tiene($permiso));
        }
    }
}
