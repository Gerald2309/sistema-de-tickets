<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const RESTRICCION = 'incidentes_calificacion_entre_1_y_5';

    // La calificación va de 1 a 5 también en la base: no depende solo de que la validación se cumpla.
    public function up(): void
    {
        DB::statement('ALTER TABLE incidentes ADD CONSTRAINT '.self::RESTRICCION.' CHECK (calificacion BETWEEN 1 AND 5)');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE incidentes DROP CHECK '.self::RESTRICCION);
    }
};
