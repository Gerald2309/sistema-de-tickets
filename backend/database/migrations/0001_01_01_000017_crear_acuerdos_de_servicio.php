<?php

use App\Incidentes\Dominio\PlazosDeServicio;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// Acuerdo de servicio de cada empresa: plazos de primera respuesta y de resolución por prioridad, y si corren en
// horario laboral. Cada incidente guarda además su límite de primera respuesta y cuándo se le respondió. Los que
// ya existían toman el límite de los plazos predeterminados y la respuesta de su primer mensaje público de otra
// persona o, si no lo hubo, de su resolución.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('acuerdos_de_servicio', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cliente_id')->unique()->constrained('clientes')->cascadeOnDelete();
            $table->boolean('horario_laboral')->default(false);
            $table->json('plazos');
            $table->timestamps();
        });

        Schema::table('incidentes', function (Blueprint $table) {
            $table->timestamp('vence_respuesta_en')->nullable()->after('fecha_vencimiento');
            $table->timestamp('primera_respuesta_en')->nullable()->after('vence_respuesta_en');
        });

        foreach (PlazosDeServicio::PREDETERMINADOS as $prioridad => $plazo) {
            DB::table('incidentes')->where('prioridad', $prioridad)->update([
                'vence_respuesta_en' => DB::raw('date_add(created_at, interval '.$plazo['respuesta_minutos'].' minute)'),
            ]);
        }

        $primerMensaje = DB::table('mensajes_incidente')
            ->join('incidentes', 'incidentes.id', '=', 'mensajes_incidente.incidente_id')
            ->where('mensajes_incidente.interno', false)
            ->whereColumn('mensajes_incidente.usuario_id', '<>', 'incidentes.usuario_creador_id')
            ->selectRaw('mensajes_incidente.incidente_id, min(mensajes_incidente.created_at) as momento')
            ->groupBy('mensajes_incidente.incidente_id');

        DB::table('incidentes')
            ->joinSub($primerMensaje, 'mensaje', 'mensaje.incidente_id', '=', 'incidentes.id')
            ->update(['incidentes.primera_respuesta_en' => DB::raw('mensaje.momento')]);

        DB::table('incidentes')
            ->whereNull('primera_respuesta_en')
            ->whereNotNull('resuelto_en')
            ->update(['primera_respuesta_en' => DB::raw('resuelto_en')]);
    }

    public function down(): void
    {
        Schema::table('incidentes', function (Blueprint $table) {
            $table->dropColumn(['vence_respuesta_en', 'primera_respuesta_en']);
        });

        Schema::dropIfExists('acuerdos_de_servicio');
    }
};
