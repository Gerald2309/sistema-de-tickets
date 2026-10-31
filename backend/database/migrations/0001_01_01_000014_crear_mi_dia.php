<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// «Mi día»: el espacio personal de quien atiende incidentes para organizar su trabajo. Todo pertenece a una
// persona y solo ella lo ve; nada de esto cambia el incidente ni lo ve quien lo reportó.
return new class extends Migration
{
    private const UNA_COSA_POR_ENTRADA = 'entradas_del_plan_incidente_o_nota';

    public function up(): void
    {
        // Notas libres («llamar a…», «enviar mensaje a…»). Si mencionan un código, quedan ligadas a ese incidente.
        Schema::create('notas_personales', function (Blueprint $table) {
            $table->id();
            $table->foreignId('usuario_id')->constrained('users')->cascadeOnDelete();
            $table->string('texto', 200);
            $table->foreignId('incidente_id')->nullable()->constrained('incidentes')->nullOnDelete();
            $table->boolean('hecha')->default(false);
            $table->timestamps();

            $table->index(['usuario_id', 'id']);
        });

        // Qué hará cada persona, qué día y a qué hora: un incidente o una nota. La hora y la duración van en
        // minutos del día local de esa persona (es su agenda, no un instante): 600 son las 10:00.
        Schema::create('entradas_del_plan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('usuario_id')->constrained('users')->cascadeOnDelete();
            $table->date('fecha');
            $table->unsignedSmallInteger('inicio');
            $table->unsignedSmallInteger('duracion');
            $table->foreignId('incidente_id')->nullable()->constrained('incidentes')->cascadeOnDelete();
            $table->foreignId('nota_id')->nullable()->constrained('notas_personales')->cascadeOnDelete();
            $table->timestamps();

            // La agenda de un día se lee en orden; un incidente va una vez por día y una nota tiene un solo lugar.
            $table->index(['usuario_id', 'fecha', 'inicio']);
            $table->unique(['usuario_id', 'fecha', 'incidente_id']);
            $table->unique('nota_id');
        });
        DB::statement('ALTER TABLE entradas_del_plan ADD CONSTRAINT '.self::UNA_COSA_POR_ENTRADA
            .' CHECK ((incidente_id IS NULL) <> (nota_id IS NULL))');

        // Marca propia de «bloqueado», con su motivo: recuerda por qué algo está quieto. No pausa el vencimiento
        // ni avisa a nadie.
        Schema::create('bloqueos_personales', function (Blueprint $table) {
            $table->foreignId('usuario_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('incidente_id')->constrained('incidentes')->cascadeOnDelete();
            $table->string('motivo', 120);
            $table->timestamps();

            $table->primary(['usuario_id', 'incidente_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bloqueos_personales');
        Schema::dropIfExists('entradas_del_plan');
        Schema::dropIfExists('notas_personales');
    }
};
