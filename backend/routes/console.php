<?php

use App\Http\Controllers\DocumentacionController;
use App\Identidad\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\Facades\Storage;

// Limpia los tokens vencidos (requiere ejecutar `schedule:run` cada minuto en el despliegue).
Schedule::command('sanctum:prune-expired', ['--hours=24'])->daily();

// Deja la aplicación lista cada vez que arranca el contenedor (docker compose up -d): aplica las migraciones
// pendientes, siembra los datos solo si todavía no hay ningún usuario y precalcula la documentación de la API,
// que es pública: generarla en cada visita tarda segundos. Se puede repetir sin efectos.
Artisan::command('app:preparar', function () {
    $this->call('migrate', ['--force' => true]);

    if (User::query()->exists()) {
        $this->info('La base ya tiene datos: no se siembra.');
    } else {
        $this->call('db:seed', ['--force' => true]);
    }

    $this->call('scramble:export', ['--path' => Storage::disk('local')->path(DocumentacionController::ARCHIVO)]);
})->purpose('Migra la base, la siembra si está vacía y precalcula la documentación de la API');
