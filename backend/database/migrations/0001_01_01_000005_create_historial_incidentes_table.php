<?php

use App\Incidentes\Dominio\CampoHistorial;
use App\Incidentes\Dominio\EventoHistorial;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Solo se inserta, nunca se actualiza: por eso no hay updated_at.
        Schema::create('historial_incidentes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('incidente_id')->constrained('incidentes')->restrictOnDelete();
            $table->foreignId('usuario_id')->constrained('users')->restrictOnDelete();
            $table->enum('evento', EventoHistorial::cases());
            $table->enum('campo', CampoHistorial::cases())->nullable();
            $table->string('valor_anterior')->nullable();
            $table->string('valor_nuevo')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });

        // Red de seguridad: `campo` y los valores solo existen cuando el evento es una modificación.
        DB::statement(<<<'SQL'
            ALTER TABLE historial_incidentes
            ADD CONSTRAINT historial_incidentes_evento_campo_check CHECK (
                (evento = 'modificacion' AND campo IS NOT NULL)
                OR (evento <> 'modificacion' AND campo IS NULL AND valor_anterior IS NULL AND valor_nuevo IS NULL)
            )
            SQL);
    }

    public function down(): void
    {
        Schema::dropIfExists('historial_incidentes');
    }
};
