<?php

use App\Auditoria\Dominio\AccionAuditoria;
use App\Identidad\Dominio\ClavePermiso;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

// Permiso para administrar automatizaciones y sus acciones en la auditoría. Las bases ya sembradas no vuelven
// a pasar por los seeders: el permiso se crea aquí y se le da al rol Administrador, si existe.
return new class extends Migration
{
    private const ACCIONES = [
        AccionAuditoria::AutomatizacionCreada,
        AccionAuditoria::AutomatizacionModificada,
        AccionAuditoria::AutomatizacionActivada,
        AccionAuditoria::AutomatizacionDesactivada,
        AccionAuditoria::AutomatizacionEliminada,
    ];

    public function up(): void
    {
        $this->accionesDeAuditoria(array_column(AccionAuditoria::cases(), 'value'));

        $permisoId = DB::table('permisos')->insertGetId(['clave' => ClavePermiso::AdministrarAutomatizaciones->value]);
        $administrador = DB::table('roles')->where('nombre', 'Administrador')->value('id');

        if ($administrador !== null) {
            DB::table('permiso_rol')->insert(['rol_id' => $administrador, 'permiso_id' => $permisoId]);
        }
    }

    public function down(): void
    {
        $permiso = DB::table('permisos')->where('clave', ClavePermiso::AdministrarAutomatizaciones->value);
        DB::table('permiso_rol')->whereIn('permiso_id', $permiso->clone()->select('id'))->delete();
        $permiso->delete();

        $acciones = array_column(self::ACCIONES, 'value');
        DB::table('auditoria')->whereIn('accion', $acciones)->delete();
        $this->accionesDeAuditoria(array_values(array_diff(array_column(AccionAuditoria::cases(), 'value'), $acciones)));
    }

    /**
     * La columna es un ENUM de MySQL: se reescribe con la lista completa.
     *
     * @param  list<string>  $valores
     */
    private function accionesDeAuditoria(array $valores): void
    {
        $lista = implode(', ', array_map(fn (string $valor) => DB::getPdo()->quote($valor), $valores));
        DB::statement("ALTER TABLE auditoria MODIFY accion ENUM({$lista}) NOT NULL");
    }
};
