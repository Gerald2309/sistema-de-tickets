<?php

use App\Auditoria\Dominio\AccionAuditoria;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Solo se inserta, nunca se actualiza ni se borra: por eso no hay updated_at.
        Schema::create('auditoria', function (Blueprint $table) {
            $table->id();
            // Quien hizo la acción. Nulo en un inicio de sesión fallido: aún no hay usuario autenticado.
            $table->foreignId('usuario_id')->nullable()->constrained('users')->restrictOnDelete();
            $table->enum('accion', AccionAuditoria::cases());
            // Usuario sobre el que recae la acción, cuando la hay.
            $table->foreignId('usuario_afectado_id')->nullable()->constrained('users')->restrictOnDelete();
            // Mensaje descriptivo de un solo hecho (por ejemplo, el rol o el permiso). No se filtra ni se ordena.
            $table->string('detalle')->nullable();
            $table->string('ip', 45)->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index('created_at');
            $table->index(['accion', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('auditoria');
    }
};
