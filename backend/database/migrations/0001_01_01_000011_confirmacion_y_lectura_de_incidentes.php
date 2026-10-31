<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Cómo calificó la atención quien reportó el incidente al confirmar que quedó solucionado (1 a 5).
        Schema::table('incidentes', function (Blueprint $table) {
            $table->unsignedTinyInteger('calificacion')->nullable()->after('solucion');
        });

        // Hasta qué mensaje leyó cada persona la conversación de un incidente: lo posterior que escribió otra
        // persona está sin leer. Una fila por persona e incidente; se actualiza, no se acumula.
        Schema::create('lecturas_conversacion', function (Blueprint $table) {
            $table->foreignId('incidente_id')->constrained('incidentes')->cascadeOnDelete();
            $table->foreignId('usuario_id')->constrained('users')->cascadeOnDelete();
            $table->unsignedBigInteger('ultimo_mensaje_leido_id');

            $table->primary(['incidente_id', 'usuario_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lecturas_conversacion');

        Schema::table('incidentes', function (Blueprint $table) {
            $table->dropColumn('calificacion');
        });
    }
};
