<?php

namespace Database\Seeders;

use App\Dominio\ClavePermiso;
use App\Models\Permiso;
use App\Models\Rol;
use Illuminate\Database\Seeder;

class RolesSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->rolesIniciales() as $nombre => $permisos) {
            $rol = Rol::firstOrCreate(['nombre' => $nombre]);

            // Un rol que ya existe se respeta: es configuración editable (RF-21, RF-23).
            if ($rol->wasRecentlyCreated) {
                $claves = array_map(fn (ClavePermiso $permiso) => $permiso->value, $permisos);
                $rol->permisos()->sync(Permiso::whereIn('clave', $claves)->pluck('id'));
            }
        }
    }

    /**
     * Permisos iniciales de cada rol. `ver_todos` ya incluye ver lo propio y
     * `editar_todos` editar lo visible, por eso no se siembran junto a ellos.
     *
     * @return array<string, list<ClavePermiso>>
     */
    private function rolesIniciales(): array
    {
        $soporte = [
            ClavePermiso::CrearIncidente,
            ClavePermiso::VerTodos,
            ClavePermiso::EditarTodos,
            ClavePermiso::CambiarEstado,
            ClavePermiso::Reasignar,
            ClavePermiso::VerHistorial,
            ClavePermiso::VerDashboard,
        ];

        return [
            'Administrador' => [
                ClavePermiso::CrearIncidente,
                ClavePermiso::VerTodos,
                ClavePermiso::EditarTodos,
                ClavePermiso::CambiarEstado,
                ClavePermiso::Reasignar,
                ClavePermiso::Borrar,
                ClavePermiso::VerHistorial,
                ClavePermiso::VerDashboard,
                ClavePermiso::AdministrarRoles,
                ClavePermiso::AdministrarUsuarios,
            ],
            'Solicitante' => [
                ClavePermiso::CrearIncidente,
                ClavePermiso::VerPropios,
                ClavePermiso::EditarPropios,
                ClavePermiso::VerHistorial,
                ClavePermiso::VerDashboard,
            ],
            'Soporte N1' => $soporte,
            'Soporte N2' => $soporte,
            'Soporte N3' => $soporte,
        ];
    }
}
