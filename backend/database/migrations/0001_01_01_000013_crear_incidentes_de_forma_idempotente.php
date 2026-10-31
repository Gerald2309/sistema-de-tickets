<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // La clave que envía el cliente al crear (cabecera Idempotency-Key). Única por persona: si la petición se
    // reintenta (red caída, doble clic), la base impide el duplicado aunque los dos envíos lleguen a la vez.
    public function up(): void
    {
        Schema::table('incidentes', function (Blueprint $table) {
            $table->uuid('clave_de_idempotencia')->nullable()->after('cliente_id');
            $table->unique(['usuario_creador_id', 'clave_de_idempotencia']);
        });
    }

    public function down(): void
    {
        Schema::table('incidentes', function (Blueprint $table) {
            $table->dropUnique(['usuario_creador_id', 'clave_de_idempotencia']);
            $table->dropColumn('clave_de_idempotencia');
        });
    }
};
