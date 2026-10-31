<?php

use App\Auditoria\Dominio\AccionAuditoria;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Empresas cliente de Psicol (F-10). Una persona pertenece como mucho a una; el personal de Psicol, a ninguna.
// El incidente guarda la empresa del momento en que se reportó: si la persona cambia de empresa, sus
// incidentes anteriores se quedan en la anterior. Por eso es un dato propio del incidente y no se deriva.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clientes', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 150)->unique();
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('cliente_id')->nullable()->after('rol_id')->constrained('clientes')->restrictOnDelete();
        });

        Schema::table('incidentes', function (Blueprint $table) {
            $table->foreignId('cliente_id')->nullable()->after('usuario_asignado_id')->constrained('clientes')->restrictOnDelete();

            // Filtro del listado por empresa; como los demás índices de filtro, termina en deleted_at.
            $table->index(['cliente_id', 'deleted_at']);
        });

        // La columna enumera las acciones auditables: se amplía con las de empresas.
        Schema::table('auditoria', function (Blueprint $table) {
            $table->enum('accion', AccionAuditoria::cases())->change();
        });
    }

    public function down(): void
    {
        Schema::table('incidentes', function (Blueprint $table) {
            $table->dropConstrainedForeignId('cliente_id');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('cliente_id');
        });

        Schema::dropIfExists('clientes');
    }
};
