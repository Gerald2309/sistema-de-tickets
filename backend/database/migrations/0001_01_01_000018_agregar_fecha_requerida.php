<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Para cuándo necesita la solución quien reporta. Solo informa a soporte: el compromiso sigue siendo la fecha de
// vencimiento, que sale del acuerdo de servicio.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('incidentes', function (Blueprint $table) {
            $table->dateTime('fecha_requerida')->nullable()->after('fecha_vencimiento');
        });
    }

    public function down(): void
    {
        Schema::table('incidentes', function (Blueprint $table) {
            $table->dropColumn('fecha_requerida');
        });
    }
};
