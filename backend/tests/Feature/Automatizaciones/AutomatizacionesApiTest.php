<?php

namespace Tests\Feature\Automatizaciones;

use App\Auditoria\Dominio\AccionAuditoria;
use App\Auditoria\Models\Auditoria;
use App\Automatizaciones\Models\Automatizacion;
use App\Identidad\Dominio\ClavePermiso;
use App\Identidad\Models\Cliente;
use App\Identidad\Models\User;
use App\Incidentes\Dominio\Categoria;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;
use Tests\Unit\Automatizaciones\PasosDeEjemplo;

class AutomatizacionesApiTest extends TestCase
{
    use PasosDeEjemplo;

    private User $administrador;

    private User $diego;

    protected function setUp(): void
    {
        parent::setUp();

        $this->prepararDatosBase();
        $this->administrador = $this->usuarioConPermisos(ClavePermiso::AdministrarAutomatizaciones);
        $this->diego = $this->crearUsuario('Soporte N2');
    }

    /**
     * @param  array<string, mixed>  $cambios
     * @return array<string, mixed>
     */
    private function datos(array $cambios = []): array
    {
        return $cambios + [
            'nombre' => 'Red a Soporte N2',
            'activa' => true,
            'disparador' => 'creacion',
            'pasos' => [self::condicion('categoria', ['red'], si: [self::asignar($this->diego->id)])],
        ];
    }

    private function como(?User $persona = null): static
    {
        return $this->conToken($this->iniciarSesion($persona ?? $this->administrador));
    }

    private function crear(): int
    {
        return $this->como()->postJson('/api/v1/automatizaciones', $this->datos())->assertCreated()->json('data.id');
    }

    /**
     * @return list<array{string, string|null}>
     */
    private function auditado(): array
    {
        return Auditoria::orderBy('id')->get()->map(fn (Auditoria $registro) => [$registro->accion->value, $registro->detalle])->all();
    }

    public function test_crea_una_y_la_devuelve_con_su_resumen(): void
    {
        $this->como()->postJson('/api/v1/automatizaciones', $this->datos())
            ->assertCreated()
            ->assertJsonPath('data.nombre', 'Red a Soporte N2')
            ->assertJsonPath('data.activa', true)
            ->assertJsonPath('data.disparador', 'creacion')
            ->assertJsonPath('data.espera_minutos', null)
            ->assertJsonPath('data.pasos', $this->datos()['pasos'])
            ->assertJsonPath('data.autor.id', $this->administrador->id)
            ->assertJsonPath('data.aplicaciones', 0)
            ->assertJsonPath('data.ultima_aplicacion_en', null);

        $this->assertSame([[AccionAuditoria::AutomatizacionCreada->value, '«Red a Soporte N2», activa']], $this->auditado());
    }

    public function test_al_editarla_pasa_a_nombre_de_quien_la_edita_y_la_auditoria_dice_que_cambio(): void
    {
        $id = $this->crear();
        $otra = $this->usuarioConPermisos(ClavePermiso::AdministrarAutomatizaciones);

        $this->como($otra)->putJson("/api/v1/automatizaciones/{$id}", $this->datos([
            'nombre' => 'Red y telefonía a N2',
            'activa' => false,
            'pasos' => [self::condicion('categoria', ['red', 'telefonia'], si: [self::asignar($this->diego->id)])],
        ]))->assertOk()->assertJsonPath('data.autor.id', $otra->id);

        $this->assertSame([
            [AccionAuditoria::AutomatizacionModificada->value, '«Red y telefonía a N2»: cambió el nombre (antes «Red a Soporte N2») y los pasos'],
            [AccionAuditoria::AutomatizacionDesactivada->value, '«Red y telefonía a N2»'],
        ], array_slice($this->auditado(), 1));
    }

    public function test_guardarla_igual_no_deja_nada_en_la_auditoria(): void
    {
        $id = $this->crear();

        $this->como()->putJson("/api/v1/automatizaciones/{$id}", $this->datos())->assertOk();

        $this->assertCount(1, $this->auditado());
    }

    public function test_se_enciende_y_se_apaga_desde_la_lista(): void
    {
        $id = $this->crear();

        $this->como()->patchJson("/api/v1/automatizaciones/{$id}/estado", ['activa' => false])->assertOk()->assertJsonPath('data.activa', false);
        $this->como()->patchJson("/api/v1/automatizaciones/{$id}/estado", ['activa' => true])->assertOk()->assertJsonPath('data.activa', true);

        $this->assertSame([AccionAuditoria::AutomatizacionDesactivada->value, AccionAuditoria::AutomatizacionActivada->value], array_column(array_slice($this->auditado(), 1), 0));
    }

    public function test_no_se_enciende_una_que_no_hace_nada(): void
    {
        $sinAcciones = Automatizacion::factory()->inactiva()->conPasos([self::condicion('prioridad', ['alta'])])->create();

        $this->como()->patchJson("/api/v1/automatizaciones/{$sinAcciones->id}/estado", ['activa' => true])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['activa' => 'Agregue al menos una acción antes de activarla.'], 'errors');
    }

    public function test_al_eliminarla_queda_en_la_auditoria(): void
    {
        $id = $this->crear();

        $this->como()->deleteJson("/api/v1/automatizaciones/{$id}")->assertNoContent();

        $this->assertNull(Automatizacion::find($id));
        $this->assertSame([AccionAuditoria::AutomatizacionEliminada->value, '«Red a Soporte N2»'], $this->auditado()[1]);
    }

    /**
     * @return array<string, array{callable(self): array<string, mixed>, string, string}>
     */
    public static function invalidas(): array
    {
        return [
            'asignar a quien no atiende' => [
                fn (self $prueba) => ['pasos' => [self::asignar($prueba->crearUsuario()->id)]],
                'pasos.0.usuario_id', 'Esa persona no está activa o no atiende incidentes.',
            ],
            'una empresa que no existe' => [
                fn (self $prueba) => ['pasos' => [self::condicion('empresa', [Cliente::factory()->create()->id, 999_999], si: [self::asignar($prueba->diego->id)])]],
                'pasos.0.valores', 'Alguna de las empresas no existe.',
            ],
            'avisar a un rol que no existe' => [
                fn () => ['pasos' => [self::avisarAlRol(999_999)]],
                'pasos.0.id', 'Ese rol no existe.',
            ],
            'una variable que no existe' => [
                fn () => ['pasos' => [self::responder('Hola {telefono}')]],
                'pasos.0.mensaje', 'No existe {telefono}. Puede usar: {nombre}, {número}, {título}, {empresa}, {prioridad}.',
            ],
            'un disparador por tiempo sin su espera' => [
                fn () => ['disparador' => 'sin_respuesta'],
                'espera_minutos', 'La espera debe ser de 1 minuto a 90 días.',
            ],
            'activa sin acciones' => [
                fn () => ['pasos' => [self::condicion('prioridad', ['alta'])]],
                'pasos', 'Agregue al menos una acción antes de activarla.',
            ],
        ];
    }

    /**
     * @param  callable(self): array<string, mixed>  $cambios
     */
    #[DataProvider('invalidas')]
    public function test_rechaza_lo_que_no_sirve_y_senala_el_dato(callable $cambios, string $ruta, string $mensaje): void
    {
        $this->como()->postJson('/api/v1/automatizaciones', $this->datos($cambios($this)))
            ->assertUnprocessable()
            ->assertJsonValidationErrors([$ruta => $mensaje], 'errors');

        $this->assertSame(0, Automatizacion::count());
    }

    public function test_probar_dice_que_habria_hecho_sin_cambiar_nada(): void
    {
        $solicitante = $this->crearUsuario();
        $this->incidenteDe($solicitante, ['categoria' => Categoria::Correo]);
        $deRed = $this->incidenteDe($solicitante, ['categoria' => Categoria::Red, 'titulo' => 'Sin internet']);

        $this->como()->postJson('/api/v1/automatizaciones/prueba', ['pasos' => $this->datos()['pasos']])
            ->assertOk()
            ->assertJsonPath('data.evaluados', 2)
            ->assertJsonPath('data.con_acciones', 1)
            ->assertJsonPath('data.caminos', [
                ['decisiones' => [['campo' => 'categoria', 'cumple' => true]], 'acciones' => ['asignar'], 'cantidad' => 1],
                ['decisiones' => [['campo' => 'categoria', 'cumple' => false]], 'acciones' => [], 'cantidad' => 1],
            ])
            ->assertJsonPath('data.ejemplos.0.titulo', 'Sin internet');

        $this->assertNull($deRed->refresh()->usuario_asignado_id);
    }

    public function test_sin_el_permiso_no_las_ve(): void
    {
        $this->como($this->usuarioConPermisos(ClavePermiso::VerTodos, ClavePermiso::AdministrarUsuarios))
            ->getJson('/api/v1/automatizaciones')
            ->assertForbidden();
    }

    public function test_quien_las_arma_puede_elegir_personas_roles_y_empresas(): void
    {
        foreach (['/api/v1/personas', '/api/v1/roles', '/api/v1/clientes'] as $ruta) {
            $this->como()->getJson($ruta)->assertOk();
        }
    }
}
