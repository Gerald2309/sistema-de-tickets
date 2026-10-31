<?php

use App\Dominio\Estado;
use App\Dominio\Prioridad;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('incidentes', function (Blueprint $table) {
            $table->id();
            $table->string('titulo');
            $table->text('descripcion');
            $table->enum('prioridad', Prioridad::cases())->default('media');
            $table->enum('estado', Estado::cases())->default('abierto');
            $table->foreignId('usuario_creador_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('usuario_asignado_id')->nullable()->constrained('users')->restrictOnDelete();
            $table->dateTime('fecha_vencimiento');
            $table->timestamps();
            $table->softDeletes();

            // Todo índice de filtro u orden termina en deleted_at: los conteos se resuelven sin leer la fila.
            $table->index(['estado', 'deleted_at']);
            $table->index(['prioridad', 'deleted_at']);
            $table->index(['fecha_vencimiento', 'deleted_at']);
            $table->index(['created_at', 'deleted_at']);
            $table->index(['estado', 'prioridad', 'deleted_at']); // dashboard (consulta combinada)
            $table->fullText(['titulo', 'descripcion']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('incidentes');
    }
};
