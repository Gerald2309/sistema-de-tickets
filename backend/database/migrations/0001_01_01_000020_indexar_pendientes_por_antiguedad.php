<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// «Cuánto llevan esperando» cuenta los pendientes por fecha de creación: con este índice MySQL los cuenta sin leer
// las filas (índice que cubre la consulta).
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('incidentes', function (Blueprint $table) {
            $table->index(['estado', 'deleted_at', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::table('incidentes', function (Blueprint $table) {
            $table->dropIndex(['estado', 'deleted_at', 'created_at']);
        });
    }
};
