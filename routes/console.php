<?php

use Illuminate\Support\Facades\Schedule;

// Limpia los tokens vencidos (requiere ejecutar `schedule:run` cada minuto en el despliegue).
Schedule::command('sanctum:prune-expired', ['--hours=24'])->daily();
