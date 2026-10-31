<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            PermisosSeeder::class,
            RolesSeeder::class,
            AdministradorSeeder::class,
        ]);

        if (! app()->isProduction()) {
            $this->call(DatosDemoSeeder::class);
        }
    }
}
