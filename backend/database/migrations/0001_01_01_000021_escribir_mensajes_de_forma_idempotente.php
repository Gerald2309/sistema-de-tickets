<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// La clave que envía el cliente al escribir un mensaje: si reintenta el envío, la API devuelve el mensaje que ya
// guardó en vez de duplicarlo en la conversación. Única por autor; vacía en los mensajes sin clave.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mensajes_incidente', function (Blueprint $table) {
            $table->uuid('clave_de_idempotencia')->nullable()->after('interno');
            $table->unique(['usuario_id', 'clave_de_idempotencia']);
        });
    }

    public function down(): void
    {
        Schema::table('mensajes_incidente', function (Blueprint $table) {
            $table->dropUnique(['usuario_id', 'clave_de_idempotencia']);
            $table->dropColumn('clave_de_idempotencia');
        });
    }
};
