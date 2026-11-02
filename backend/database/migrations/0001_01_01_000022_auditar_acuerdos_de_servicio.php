<?php

use App\Auditoria\Dominio\AccionAuditoria;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

// La auditoría registra también los cambios del acuerdo de servicio (SLA) de cada empresa. La columna es un ENUM
// de MySQL: el valor nuevo se agrega a la lista.
return new class extends Migration
{
    public function up(): void
    {
        $this->acciones(array_column(AccionAuditoria::cases(), 'value'));
    }

    public function down(): void
    {
        DB::table('auditoria')->where('accion', AccionAuditoria::AcuerdoDeServicioModificado->value)->delete();
        $this->acciones(array_values(array_diff(
            array_column(AccionAuditoria::cases(), 'value'),
            [AccionAuditoria::AcuerdoDeServicioModificado->value],
        )));
    }

    /**
     * @param  list<string>  $valores
     */
    private function acciones(array $valores): void
    {
        $lista = implode(', ', array_map(fn (string $valor) => DB::getPdo()->quote($valor), $valores));
        DB::statement("ALTER TABLE auditoria MODIFY accion ENUM({$lista}) NOT NULL");
    }
};
