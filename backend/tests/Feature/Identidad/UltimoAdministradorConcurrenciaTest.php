<?php

namespace Tests\Feature\Identidad;

use App\Identidad\Acciones\BloqueoAdministracion;
use App\Identidad\Acciones\ModificarUsuario;
use App\Identidad\Dominio\ClavePermiso;
use App\Identidad\Dominio\Excepciones\UltimoAdministrador;
use App\Identidad\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Tests\TestCaseConDatosConfirmados;

// Dos administradores se desactivan a la vez: sin el bloqueo común cada uno vería al otro activo y
// quedarían cero. Se usan dos conexiones reales a la misma base, por eso los datos deben estar confirmados.
// Demuestra que la segunda operación espera a la primera y luego ve su resultado; no explora todas las
// intercalaciones posibles.
class UltimoAdministradorConcurrenciaTest extends TestCaseConDatosConfirmados
{
    private const SEGUNDA = 'segunda_conexion';

    protected function tearDown(): void
    {
        // Una transacción abierta de esta prueba bloquearía el vaciado de tablas.
        if (DB::connection('mysql')->transactionLevel() > 0) {
            DB::connection('mysql')->rollBack();
        }
        DB::setDefaultConnection('mysql');
        DB::purge(self::SEGUNDA);

        parent::tearDown();
    }

    private function abrirSegundaConexion(): void
    {
        config(['database.connections.'.self::SEGUNDA => config('database.connections.mysql')]);
        DB::connection(self::SEGUNDA)->statement('SET SESSION innodb_lock_wait_timeout = 1');
    }

    // Aísla el bloqueo común: quien lo tiene no ha tocado ningún usuario, y aun así la otra operación espera.
    public function test_el_bloqueo_comun_hace_esperar_a_la_otra_operacion(): void
    {
        $this->usuarioConPermisos(ClavePermiso::AdministrarUsuarios, ClavePermiso::AdministrarRoles);
        $b = $this->usuarioConPermisos(ClavePermiso::AdministrarUsuarios, ClavePermiso::AdministrarRoles);
        $this->abrirSegundaConexion();

        DB::connection('mysql')->beginTransaction();
        app(BloqueoAdministracion::class)->adquirir();

        DB::setDefaultConnection(self::SEGUNDA);
        $this->expectException(QueryException::class);
        $this->expectExceptionMessage('Lock wait timeout');

        app(ModificarUsuario::class)->ejecutar($b, $b, ['activo' => false], null);
    }

    public function test_desactivar_a_dos_administradores_a_la_vez_deja_siempre_uno(): void
    {
        $a = $this->usuarioConPermisos(ClavePermiso::AdministrarUsuarios, ClavePermiso::AdministrarRoles);
        $b = $this->usuarioConPermisos(ClavePermiso::AdministrarUsuarios, ClavePermiso::AdministrarRoles);
        $this->abrirSegundaConexion();

        // Conexión 1: desactiva a A y todavía no confirma.
        DB::connection('mysql')->beginTransaction();
        app(BloqueoAdministracion::class)->adquirir();
        DB::table('users')->where('id', $a->id)->update(['activo' => false]);

        // Conexión 2: intenta desactivar a B y debe esperar el bloqueo hasta agotar el tiempo.
        DB::setDefaultConnection(self::SEGUNDA);
        try {
            app(ModificarUsuario::class)->ejecutar($b, $b, ['activo' => false], null);
            $this->fail('La segunda operación no esperó al bloqueo común.');
        } catch (QueryException $excepcion) {
            $this->assertStringContainsString('Lock wait timeout', $excepcion->getMessage());
        }

        // La conexión 1 confirma; la 2 repite y ahora ve que A ya no está activo.
        DB::setDefaultConnection('mysql');
        DB::connection('mysql')->commit();

        DB::setDefaultConnection(self::SEGUNDA);
        try {
            app(ModificarUsuario::class)->ejecutar($b, $b, ['activo' => false], null);
            $this->fail('Se permitió desactivar al último administrador.');
        } catch (UltimoAdministrador) {
            DB::setDefaultConnection('mysql');
        }

        $this->assertFalse(User::findOrFail($a->id)->activo);
        $this->assertTrue(User::findOrFail($b->id)->activo);
    }
}
