<?php

use App\Identidad\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

// Limpia los tokens vencidos (requiere ejecutar `schedule:run` cada minuto en el despliegue).
Schedule::command('sanctum:prune-expired', ['--hours=24'])->daily();

// Deja la base lista cada vez que arranca el contenedor (docker compose up -d): aplica las migraciones
// pendientes y, solo si todavía no hay ningún usuario, siembra los datos. Se puede repetir sin efectos.
Artisan::command('app:preparar', function () {
    $this->call('migrate', ['--force' => true]);

    if (User::query()->exists()) {
        $this->info('La base ya tiene datos: no se siembra.');

        return;
    }

    $this->call('db:seed', ['--force' => true]);
})->purpose('Migra la base y, si está vacía, la siembra');
