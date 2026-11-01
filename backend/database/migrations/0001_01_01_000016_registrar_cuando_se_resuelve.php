<?php

use App\Incidentes\Dominio\CampoHistorial;
use App\Incidentes\Dominio\Estado;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// Cuándo se resolvió cada incidente, para medir el tiempo de resolución sin recorrer el historial. Los que ya
// estaban resueltos o cerrados lo toman de su última entrada al estado resuelto. El historial gana un índice
// para las métricas que salen de sus cambios de estado (primera atención y soluciones devueltas).
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('incidentes', function (Blueprint $table) {
            $table->timestamp('resuelto_en')->nullable()->after('fecha_vencimiento');
            $table->index(['resuelto_en', 'deleted_at']);
        });

        Schema::table('historial_incidentes', function (Blueprint $table) {
            $table->index(['campo', 'created_at']);
        });

        $ultimaResolucion = DB::table('historial_incidentes')
            ->selectRaw('incidente_id, max(created_at) as momento')
            ->where('campo', CampoHistorial::Estado->value)
            ->where('valor_nuevo', Estado::Resuelto->value)
            ->groupBy('incidente_id');

        DB::table('incidentes')
            ->joinSub($ultimaResolucion, 'resolucion', 'resolucion.incidente_id', '=', 'incidentes.id')
            ->whereIn('incidentes.estado', [Estado::Resuelto->value, Estado::Cerrado->value])
            ->update(['incidentes.resuelto_en' => DB::raw('resolucion.momento')]);
    }

    public function down(): void
    {
        Schema::table('historial_incidentes', function (Blueprint $table) {
            $table->dropIndex(['campo', 'created_at']);
        });

        Schema::table('incidentes', function (Blueprint $table) {
            $table->dropIndex(['resuelto_en', 'deleted_at']);
            $table->dropColumn('resuelto_en');
        });
    }
};
