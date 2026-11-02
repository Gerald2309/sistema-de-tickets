<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Seguridad de la cuenta: cada persona ve su inicio de sesión anterior y desde dónde tiene sesiones abiertas,
// y quien recibe una contraseña puesta por un administrador debe cambiarla antes de usar el sistema.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('ultimo_inicio_en')->nullable();
            $table->string('ultimo_inicio_ip', 45)->nullable();
            $table->timestamp('inicio_anterior_en')->nullable();
            $table->string('inicio_anterior_ip', 45)->nullable();
            $table->boolean('debe_cambiar_contrasena')->default(false);
        });

        Schema::table('personal_access_tokens', function (Blueprint $table) {
            $table->string('ip', 45)->nullable();
            $table->string('agente')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('personal_access_tokens', function (Blueprint $table) {
            $table->dropColumn(['ip', 'agente']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['ultimo_inicio_en', 'ultimo_inicio_ip', 'inicio_anterior_en', 'inicio_anterior_ip', 'debe_cambiar_contrasena']);
        });
    }
};
