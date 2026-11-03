<?php

use App\Automatizaciones\Models\Automatizacion;
use App\Automatizaciones\Trabajos\RevisarAutomatizacion;
use App\Http\Controllers\DocumentacionController;
use App\Identidad\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\Facades\Storage;

// Limpia los tokens vencidos (requiere ejecutar `schedule:run` cada minuto en el despliegue).
Schedule::command('sanctum:prune-expired', ['--hours=24'])->daily();

// Las automatizaciones por tiempo (SLA por vencer o vencido, esperas) se revisan cada cinco minutos: aquí
// solo se encola una revisión por automatización; el trabajo lo hace la cola.
Artisan::command('automatizaciones:revisar', function () {
    Automatizacion::query()->activasPorTiempo()->pluck('id')->each(fn (int $id) => RevisarAutomatizacion::dispatch($id));
})->purpose('Encola la revisión de las automatizaciones que dependen del tiempo');

Schedule::command('automatizaciones:revisar')->everyFiveMinutes()->withoutOverlapping();

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
