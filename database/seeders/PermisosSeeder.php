<?php

namespace Database\Seeders;

use App\Dominio\ClavePermiso;
use App\Models\Permiso;
use Illuminate\Database\Seeder;

class PermisosSeeder extends Seeder
{
    // Idempotente: añade los permisos nuevos del enum sin tocar los existentes.
    public function run(): void
    {
        foreach (ClavePermiso::cases() as $clave) {
            Permiso::firstOrCreate(['clave' => $clave->value]);
        }
    }
}
