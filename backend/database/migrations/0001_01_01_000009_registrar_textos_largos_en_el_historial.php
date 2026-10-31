<?php

use App\Incidentes\Dominio\CampoHistorial;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Campos que llegaron después: sus valores son textos largos (hasta 10 000 caracteres).
    private const CAMPOS_NUEVOS = ['descripcion', 'solucion'];

    public function up(): void
    {
        Schema::table('historial_incidentes', function (Blueprint $table) {
            $table->enum('campo', CampoHistorial::cases())->nullable()->change();
            $table->text('valor_anterior')->nullable()->change();
            $table->text('valor_nuevo')->nullable()->change();
        });
    }

    // Las columnas anteriores no admiten estos campos ni textos largos: sus entradas se descartan.
    public function down(): void
    {
        DB::table('historial_incidentes')->whereIn('campo', self::CAMPOS_NUEVOS)->delete();

        $camposAnteriores = array_values(array_diff(array_column(CampoHistorial::cases(), 'value'), self::CAMPOS_NUEVOS));

        Schema::table('historial_incidentes', function (Blueprint $table) use ($camposAnteriores) {
            $table->enum('campo', $camposAnteriores)->nullable()->change();
            $table->string('valor_anterior')->nullable()->change();
            $table->string('valor_nuevo')->nullable()->change();
        });
    }
};
