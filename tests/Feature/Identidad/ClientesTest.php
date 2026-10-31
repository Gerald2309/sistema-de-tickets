<?php

namespace Tests\Feature\Identidad;

use App\Auditoria\Dominio\AccionAuditoria;
use App\Auditoria\Models\Auditoria;
use App\Identidad\Dominio\ClavePermiso;
use App\Identidad\Models\Cliente;
use App\Identidad\Models\Rol;
use App\Identidad\Models\User;
use Tests\TestCase;

// Empresas cliente (F-10): alta, modificación, consulta y su relación con las personas.
class ClientesTest extends TestCase
{
    private User $administrador;

    private string $token;

    protected function setUp(): void
    {
        parent::setUp();

        $this->prepararDatosBase();
        $this->administrador = $this->usuarioConPermisos(ClavePermiso::AdministrarUsuarios);
        $this->token = $this->iniciarSesion($this->administrador);
    }

    private function ultimaAuditoria(AccionAuditoria $accion): ?Auditoria
    {
        return Auditoria::where('accion', $accion->value)->latest('id')->first();
    }

    public function test_crea_una_empresa_sin_espacios_de_mas_y_lo_audita(): void
    {
        $this->conToken($this->token)->postJson('/api/v1/clientes', ['nombre' => '  Constructora   Andina '])
            ->assertCreated()
            ->assertJsonPath('data.nombre', 'Constructora Andina')
            ->assertJsonPath('data.activo', true)
            ->assertJsonPath('data.usuarios', 0);

        $registro = $this->ultimaAuditoria(AccionAuditoria::ClienteCreado);
        $this->assertSame($this->administrador->id, $registro?->usuario_id);
        $this->assertSame('Empresa «Constructora Andina»', $registro?->detalle);
    }

    public function test_el_nombre_es_obligatorio_unico_y_acotado(): void
    {
        Cliente::factory()->create(['nombre' => 'Banco Aliado']);

        foreach ([['nombre' => ''], ['nombre' => 'Banco  Aliado'], ['nombre' => str_repeat('a', 151)], []] as $datos) {
            $this->conToken($this->token)->postJson('/api/v1/clientes', $datos)
                ->assertUnprocessable()->assertJsonValidationErrors(['nombre'], 'errors');
        }
        $this->conToken($this->token)->postJson('/api/v1/clientes', ['nombre' => 'Nueva', 'activo' => false])
            ->assertUnprocessable()->assertJsonValidationErrors(['activo'], 'errors');
    }

    public function test_renombra_y_desactiva_dejando_un_registro_por_cambio(): void
    {
        $cliente = Cliente::factory()->create(['nombre' => 'Clinica del Norte']);

        $this->conToken($this->token)->patchJson("/api/v1/clientes/{$cliente->id}", ['nombre' => 'Clínica del Norte', 'activo' => false])
            ->assertOk()
            ->assertJsonPath('data.nombre', 'Clínica del Norte')
            ->assertJsonPath('data.activo', false);

        $detalles = Auditoria::where('accion', AccionAuditoria::ClienteModificado->value)->orderBy('id')->pluck('detalle')->all();
        $this->assertSame(['Empresa «Clinica del Norte» → «Clínica del Norte»', 'Desactivada la empresa «Clínica del Norte»'], $detalles);
    }

    public function test_modificar_con_los_mismos_valores_no_deja_registro(): void
    {
        $cliente = Cliente::factory()->create(['nombre' => 'Banco Aliado']);

        $this->conToken($this->token)->patchJson("/api/v1/clientes/{$cliente->id}", ['nombre' => 'Banco Aliado', 'activo' => true])->assertOk();

        $this->assertNull($this->ultimaAuditoria(AccionAuditoria::ClienteModificado));
    }

    public function test_lista_ordenada_con_filtros_y_personas_por_empresa(): void
    {
        $banco = Cliente::factory()->create(['nombre' => 'Banco Aliado']);
        Cliente::factory()->inactivo()->create(['nombre' => 'Agencia Cerrada']);
        $this->crearUsuario('Solicitante', ['cliente_id' => $banco->id]);
        $this->crearUsuario('Solicitante', ['cliente_id' => $banco->id]);

        $todas = $this->conToken($this->token)->getJson('/api/v1/clientes')->assertOk();
        $this->assertSame(['Agencia Cerrada', 'Banco Aliado'], array_column($todas->json('data'), 'nombre'));
        $this->assertSame(2, $todas->json('data.1.usuarios'));

        $activas = $this->conToken($this->token)->getJson('/api/v1/clientes?activo=1&q=banco')->assertOk();
        $this->assertSame(['Banco Aliado'], array_column($activas->json('data'), 'nombre'));
    }

    public function test_consultan_quien_administra_usuarios_y_quien_ve_todo_y_escribe_solo_el_primero(): void
    {
        $soporte = $this->iniciarSesion($this->usuarioConPermisos(ClavePermiso::VerTodos));
        $solicitante = $this->iniciarSesion($this->usuarioConPermisos(ClavePermiso::VerPropios));

        $this->conToken($soporte)->getJson('/api/v1/clientes')->assertOk();
        $this->conToken($soporte)->postJson('/api/v1/clientes', ['nombre' => 'Otra'])->assertForbidden();
        $this->conToken($solicitante)->getJson('/api/v1/clientes')->assertForbidden();
        $this->sinCredencial()->getJson('/api/v1/clientes')->assertUnauthorized();
    }

    public function test_crea_y_modifica_personas_con_empresa_y_lo_audita(): void
    {
        $empresa = Cliente::factory()->create(['nombre' => 'Transportes Sabana']);
        $otra = Cliente::factory()->create(['nombre' => 'Banco Aliado']);
        $rol = Rol::where('nombre', 'Solicitante')->firstOrFail();

        $creada = $this->conToken($this->token)->postJson('/api/v1/usuarios', [
            'name' => 'Jorge León',
            'email' => 'jorge.leon@empresa-ejemplo.com',
            'password' => 'una contraseña inicial bastante larga',
            'rol_id' => $rol->id,
            'cliente_id' => $empresa->id,
        ])->assertCreated()->assertJsonPath('data.cliente', ['id' => $empresa->id, 'nombre' => 'Transportes Sabana']);
        $this->assertSame('Rol «Solicitante» · Empresa «Transportes Sabana»', $this->ultimaAuditoria(AccionAuditoria::UsuarioCreado)?->detalle);

        $id = $creada->json('data.id');
        $this->conToken($this->token)->patchJson("/api/v1/usuarios/{$id}", ['cliente_id' => $otra->id])
            ->assertOk()->assertJsonPath('data.cliente.nombre', 'Banco Aliado');
        $this->assertSame('Campos: cliente_id', $this->ultimaAuditoria(AccionAuditoria::UsuarioModificado)?->detalle);

        $this->conToken($this->token)->patchJson("/api/v1/usuarios/{$id}", ['cliente_id' => null])
            ->assertOk()->assertJsonPath('data.cliente', null);
    }

    public function test_no_se_asigna_una_empresa_inactiva_ni_inexistente(): void
    {
        $inactiva = Cliente::factory()->inactivo()->create();
        $persona = $this->crearUsuario();

        foreach ([$inactiva->id, 999999] as $clienteId) {
            $this->conToken($this->token)->patchJson("/api/v1/usuarios/{$persona->id}", ['cliente_id' => $clienteId])
                ->assertUnprocessable()->assertJsonValidationErrors(['cliente_id'], 'errors');
        }
    }

    public function test_el_listado_de_usuarios_filtra_por_empresa_y_el_perfil_la_muestra(): void
    {
        $empresa = Cliente::factory()->create(['nombre' => 'Banco Aliado']);
        $persona = $this->crearUsuario('Solicitante', ['cliente_id' => $empresa->id]);
        $this->crearUsuario();

        $respuesta = $this->conToken($this->token)->getJson("/api/v1/usuarios?cliente_id={$empresa->id}")->assertOk();
        $this->assertSame([$persona->id], array_column($respuesta->json('data'), 'id'));

        $this->conToken($this->iniciarSesion($persona))->getJson('/api/v1/me')
            ->assertJsonPath('data.cliente', ['id' => $empresa->id, 'nombre' => 'Banco Aliado']);
    }
}
