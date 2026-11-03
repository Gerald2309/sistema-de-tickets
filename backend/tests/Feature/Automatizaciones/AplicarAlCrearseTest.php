<?php

namespace Tests\Feature\Automatizaciones;

use App\Automatizaciones\Acciones\AplicarAutomatizaciones;
use App\Automatizaciones\Dominio\Disparador;
use App\Automatizaciones\Models\Automatizacion;
use App\Automatizaciones\Models\EjecucionDeAutomatizacion;
use App\Identidad\Models\Cliente;
use App\Identidad\Models\User;
use App\Incidentes\Dominio\CodigoIncidente;
use App\Incidentes\Dominio\Estado;
use App\Incidentes\Dominio\Prioridad;
use App\Incidentes\Models\Incidente;
use App\Incidentes\Notificaciones\AvisoDeIncidente;
use Tests\TestCase;
use Tests\Unit\Automatizaciones\PasosDeEjemplo;

class AplicarAlCrearseTest extends TestCase
{
    use PasosDeEjemplo;

    private const NOMBRE = 'Red a Soporte N2';

    private User $administrador;

    private User $diego;

    private User $solicitante;

    protected function setUp(): void
    {
        parent::setUp();

        $this->prepararDatosBase();
        $this->administrador = $this->crearUsuario('Administrador');
        $this->diego = $this->crearUsuario('Soporte N2');
        $this->solicitante = $this->crearUsuario('Solicitante', ['name' => 'Marta Ruiz', 'cliente_id' => Cliente::factory()->create(['nombre' => 'Transportes Sabana'])->id]);
    }

    /**
     * @param  list<array<string, mixed>>  $pasos
     */
    private function automatizacion(array $pasos, bool $activa = true): Automatizacion
    {
        return Automatizacion::factory()->conPasos($pasos)->create(['nombre' => self::NOMBRE, 'activa' => $activa, 'autor_id' => $this->administrador->id]);
    }

    /**
     * Lo crea el solicitante por la API, como en la aplicación: las automatizaciones corren al confirmarse.
     */
    private function crearIncidente(string $categoria = 'red'): Incidente
    {
        $id = $this->conToken($this->iniciarSesion($this->solicitante))
            ->postJson('/api/v1/incidentes', ['titulo' => 'Sin internet', 'descripcion' => 'No carga ninguna página.', 'categoria' => $categoria])
            ->assertCreated()
            ->json('data.id');

        return Incidente::findOrFail($id);
    }

    private function ejecucion(Incidente $incidente): EjecucionDeAutomatizacion
    {
        return EjecucionDeAutomatizacion::where('incidente_id', $incidente->id)->sole();
    }

    public function test_hace_sus_acciones_a_nombre_de_quien_la_guardo_y_con_su_nombre_como_origen(): void
    {
        $this->automatizacion([self::condicion('categoria', ['red'], si: [
            self::asignar($this->diego->id),
            ['tipo' => 'accion', 'accion' => 'cambiar_prioridad', 'prioridad' => 'alta'],
            self::responder('Hola {nombre}, recibimos tu caso {número} ({prioridad}).'),
            ['tipo' => 'accion', 'accion' => 'nota_interna', 'texto' => 'Cliente: {empresa}'],
        ])]);

        $incidente = $this->crearIncidente();

        $this->assertSame($this->diego->id, $incidente->usuario_asignado_id);
        $this->assertSame(Prioridad::Alta, $incidente->prioridad);
        $codigo = CodigoIncidente::deId($incidente->id);
        $this->assertSame(
            [["Hola Marta Ruiz, recibimos tu caso {$codigo} (alta).", false], ['Cliente: Transportes Sabana', true]],
            $incidente->mensajes()->orderBy('id')->get()->map(fn ($mensaje) => [$mensaje->cuerpo, $mensaje->interno])->all(),
        );
        $aSuNombre = ['usuario_id' => $this->administrador->id, 'origen' => self::NOMBRE];
        $this->assertSame(2, $incidente->mensajes()->where($aSuNombre)->count());
        $this->assertSame(2, $incidente->historial()->where($aSuNombre)->count(), 'La asignación y el cambio de prioridad.');
        $this->assertSame([4, null], [$this->ejecucion($incidente)->acciones_hechas, $this->ejecucion($incidente)->error]);
    }

    public function test_si_no_cumple_la_condicion_sigue_el_camino_no(): void
    {
        $this->automatizacion([self::condicion('categoria', ['red'], si: [self::asignar($this->diego->id)], no: [
            ['tipo' => 'accion', 'accion' => 'cambiar_prioridad', 'prioridad' => 'baja'],
        ])]);

        $incidente = $this->crearIncidente('correo');

        $this->assertNull($incidente->usuario_asignado_id);
        $this->assertSame(Prioridad::Baja, $incidente->prioridad);
    }

    public function test_una_inactiva_no_hace_nada(): void
    {
        $this->automatizacion([self::asignar($this->diego->id)], activa: false);

        $incidente = $this->crearIncidente();

        $this->assertNull($incidente->usuario_asignado_id);
        $this->assertSame(0, EjecucionDeAutomatizacion::count());
    }

    public function test_se_aplica_una_sola_vez_por_incidente(): void
    {
        $this->automatizacion([self::responder('Recibido.')]);
        $incidente = $this->crearIncidente();

        app(AplicarAutomatizaciones::class)->ejecutar($incidente->id, Disparador::Creacion);

        $this->assertSame(1, $incidente->mensajes()->count());
    }

    public function test_si_una_accion_no_se_puede_hacer_se_detiene_ahi_y_anota_por_que(): void
    {
        $this->automatizacion([
            ['tipo' => 'accion', 'accion' => 'nota_interna', 'texto' => 'Revisar'],
            ['tipo' => 'accion', 'accion' => 'cerrar', 'mensaje' => 'Lo cerramos.'],
            self::asignar($this->diego->id),
        ]);

        $incidente = $this->crearIncidente();

        $this->assertSame(Estado::Abierto, $incidente->estado);
        $this->assertNull($incidente->usuario_asignado_id);
        $this->assertSame(['Revisar'], $incidente->mensajes()->pluck('cuerpo')->all(), 'Sin el mensaje de cierre.');
        $this->assertSame(1, $this->ejecucion($incidente)->acciones_hechas);
        $this->assertStringContainsString('No se puede pasar de «abierto» a «cerrado»', (string) $this->ejecucion($incidente)->error);
    }

    public function test_no_asigna_a_quien_ya_no_atiende_incidentes(): void
    {
        $this->automatizacion([self::asignar($this->diego->id)]);
        $this->diego->update(['activo' => false]);

        $incidente = $this->crearIncidente();

        $this->assertNull($incidente->usuario_asignado_id);
        $this->assertSame('La persona a la que asigna ya no puede atender incidentes.', $this->ejecucion($incidente)->error);
    }

    public function test_si_quien_la_guardo_ya_no_esta_activo_no_hace_nada(): void
    {
        $this->automatizacion([self::asignar($this->diego->id)]);
        $this->administrador->update(['activo' => false]);

        $incidente = $this->crearIncidente();

        $this->assertNull($incidente->usuario_asignado_id);
        $this->assertSame(0, $this->ejecucion($incidente)->acciones_hechas);
    }

    public function test_avisar_a_un_rol_deja_un_aviso_a_cada_persona_activa_de_ese_rol(): void
    {
        $otra = $this->crearUsuario('Soporte N2');
        $inactiva = $this->crearUsuario('Soporte N2', ['activo' => false]);
        $this->automatizacion([self::avisarAlRol($this->diego->rol_id)]);

        $incidente = $this->crearIncidente();

        $texto = '«'.self::NOMBRE.'»: '.CodigoIncidente::deId($incidente->id).' · Sin internet';
        foreach ([$this->diego, $otra] as $persona) {
            $aviso = $persona->notifications()->sole();
            $this->assertSame(AvisoDeIncidente::class, $aviso->type);
            $this->assertSame(['incidente_id' => $incidente->id, 'texto' => $texto], $aviso->data);
        }
        $this->assertSame(0, $inactiva->notifications()->count());
        $this->assertSame("usuario.{$this->diego->id}", $this->diego->receivesBroadcastNotificationsOn());
    }
}
