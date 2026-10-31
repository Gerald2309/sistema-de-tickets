<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Conversación de cada incidente. Solo se inserta: un mensaje enviado no se edita ni se borra.
        Schema::create('mensajes_incidente', function (Blueprint $table) {
            $table->id();
            $table->foreignId('incidente_id')->constrained('incidentes')->restrictOnDelete();
            $table->foreignId('usuario_id')->constrained('users')->restrictOnDelete();
            // Puede faltar si el mensaje es solo una imagen.
            $table->text('cuerpo')->nullable();
            // Nota interna: la ve soporte, nunca el solicitante.
            $table->boolean('interno')->default(false);
            $table->timestamp('created_at')->useCurrent();

            // La conversación se lee siempre por incidente y en orden.
            $table->index(['incidente_id', 'id']);
        });

        // Las imágenes viven en el disco privado; aquí, su ubicación y lo que se muestra de ellas.
        Schema::create('adjuntos_mensaje', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mensaje_id')->constrained('mensajes_incidente')->restrictOnDelete();
            $table->string('ruta')->unique();
            $table->string('nombre');
            $table->string('tipo', 50);
            $table->unsignedInteger('bytes');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('adjuntos_mensaje');
        Schema::dropIfExists('mensajes_incidente');
    }
};
