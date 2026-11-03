<?php

use App\Automatizaciones\Dominio\Disparador;
use App\Automatizaciones\Dominio\PlazoDelSla;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Automatizaciones: reglas que hacen acciones sobre los incidentes. Lo que hacen queda a nombre de quien
// las guardó, y el historial y la conversación anotan su nombre como origen del cambio.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('automatizaciones', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 120);
            $table->boolean('activa')->default(false);
            $table->enum('disparador', Disparador::cases());
            // La medida del disparador; solo se llena la que ese disparador usa (Disparo).
            $table->unsignedInteger('espera_minutos')->nullable();
            $table->unsignedTinyInteger('porcentaje')->nullable();
            $table->enum('plazo', PlazoDelSla::cases())->nullable();
            $table->json('pasos');
            // Quien la creó o la editó por última vez.
            $table->foreignId('autor_id')->constrained('users')->restrictOnDelete();
            $table->timestamps();

            $table->index(['disparador', 'activa']);
        });

        Schema::create('ejecuciones_de_automatizacion', function (Blueprint $table) {
            $table->id();
            $table->foreignId('automatizacion_id')->constrained('automatizaciones')->cascadeOnDelete();
            $table->foreignId('incidente_id')->constrained('incidentes')->cascadeOnDelete();
            $table->unsignedSmallInteger('acciones_hechas')->default(0);
            // Por qué se detuvo, si no pudo hacer todas sus acciones.
            $table->string('error')->nullable();
            $table->timestamp('created_at')->useCurrent();

            // Una sola vez por incidente: un reintento o un disparo repetido no vuelve a hacer lo hecho.
            $table->unique(['automatizacion_id', 'incidente_id'], 'una_ejecucion_por_incidente');
        });

        foreach (['historial_incidentes', 'mensajes_incidente'] as $tabla) {
            Schema::table($tabla, function (Blueprint $table) {
                $table->string('origen', 120)->nullable()->after('usuario_id');
            });
        }
    }

    public function down(): void
    {
        foreach (['mensajes_incidente', 'historial_incidentes'] as $tabla) {
            Schema::table($tabla, function (Blueprint $table) {
                $table->dropColumn('origen');
            });
        }

        Schema::dropIfExists('ejecuciones_de_automatizacion');
        Schema::dropIfExists('automatizaciones');
    }
};
