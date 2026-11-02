<?php

namespace Tests\Feature\Identidad;

use App\Identidad\Dominio\ClavePermiso;
use Illuminate\Support\Carbon;
use Laravel\Sanctum\PersonalAccessToken;
use Tests\TestCase;

class PerfilYSesionesTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->prepararDatosBase();
    }

    public function test_me_devuelve_quien_es_y_sus_permisos(): void
    {
        $usuario = $this->usuarioConPermisos(ClavePermiso::VerTodos, ClavePermiso::CrearIncidente);

        $this->conToken($this->iniciarSesion($usuario))->getJson('/api/v1/me')
            ->assertOk()
            ->assertExactJson(['data' => [
                'id' => $usuario->id,
                'name' => $usuario->name,
                'email' => $usuario->email,
                'rol' => ['id' => $usuario->rol->id, 'nombre' => $usuario->rol->nombre],
                'cliente' => null,
                'permisos' => ['crear_incidente', 'ver_todos'],
                'debe_cambiar_contrasena' => false,
                'inicio_anterior' => null,
            ]]);

        $this->sinCredencial()->getJson('/api/v1/me')->assertUnauthorized();
    }

    public function test_al_superar_el_maximo_de_sesiones_se_cierran_las_mas_antiguas(): void
    {
        config(['seguridad.sesiones_maximas' => 2]);
        $usuario = $this->crearUsuario();

        $primera = $this->iniciarSesion($usuario);
        $segunda = $this->iniciarSesion($usuario);
        $tercera = $this->iniciarSesion($usuario);

        $this->assertSame(2, $usuario->tokens()->count());
        $this->conToken($primera)->getJson('/api/v1/me')->assertUnauthorized();
        $this->conToken($segunda)->getJson('/api/v1/me')->assertOk();
        $this->conToken($tercera)->getJson('/api/v1/me')->assertOk();
    }

    public function test_las_sesiones_de_otra_persona_no_se_tocan(): void
    {
        config(['seguridad.sesiones_maximas' => 1]);
        $otra = $this->crearUsuario();
        $tokenDeOtra = $this->iniciarSesion($otra);

        $usuario = $this->crearUsuario();
        $this->iniciarSesion($usuario);
        $this->iniciarSesion($usuario);

        $this->conToken($tokenDeOtra)->getJson('/api/v1/me')->assertOk();
    }

    public function test_el_perfil_muestra_el_inicio_de_sesion_anterior_y_desde_donde(): void
    {
        $usuario = $this->crearUsuario();
        $this->travelTo(Carbon::parse('2026-11-02 13:00:00', 'UTC'));
        $this->withServerVariables(['REMOTE_ADDR' => '190.24.10.5'])->iniciarSesion($usuario);
        $this->travelTo(Carbon::parse('2026-11-02 15:30:00', 'UTC'));
        $token = $this->withServerVariables(['REMOTE_ADDR' => '181.50.3.9'])->iniciarSesion($usuario);

        $this->conToken($token)->getJson('/api/v1/me')
            ->assertOk()
            ->assertJsonPath('data.inicio_anterior', ['en' => '2026-11-02T13:00:00Z', 'ip' => '190.24.10.5']);
    }

    public function test_lista_solo_las_sesiones_propias_con_su_origen_y_sin_el_token(): void
    {
        $usuario = $this->crearUsuario();
        $this->withHeader('User-Agent', 'Firefox en el portátil')->iniciarSesion($usuario);
        $actual = $this->withHeader('User-Agent', 'Chrome en el celular')->iniciarSesion($usuario);
        $this->iniciarSesion($this->crearUsuario());

        $sesiones = $this->conToken($actual)->getJson('/api/v1/perfil/sesiones')->assertOk()->assertJsonCount(2, 'data');

        $this->assertSame(['Chrome en el celular', 'Firefox en el portátil'], $sesiones->json('data.*.agente'));
        $this->assertSame([true, false], $sesiones->json('data.*.actual'));
        $this->assertSame(['id', 'ip', 'agente', 'iniciada_en', 'ultimo_uso_en', 'actual'], array_keys($sesiones->json('data.0')));
    }

    public function test_cierra_una_sesion_propia_pero_no_la_actual_ni_la_de_otra_persona(): void
    {
        $usuario = $this->crearUsuario();
        $otraSesion = $this->iniciarSesion($usuario);
        $actual = $this->iniciarSesion($usuario);
        $deOtraPersona = $this->iniciarSesion($this->crearUsuario());
        [$idOtraSesion, $idActual] = $usuario->tokens()->orderBy('id')->pluck('id')->all();
        $idDeOtraPersona = PersonalAccessToken::query()->max('id');

        $this->conToken($actual)->deleteJson("/api/v1/perfil/sesiones/{$idDeOtraPersona}")->assertNotFound();
        $this->conToken($actual)->deleteJson("/api/v1/perfil/sesiones/{$idActual}")->assertNotFound();
        $this->conToken($actual)->deleteJson("/api/v1/perfil/sesiones/{$idOtraSesion}")->assertNoContent();

        $this->conToken($otraSesion)->getJson('/api/v1/me')->assertUnauthorized();
        $this->conToken($actual)->getJson('/api/v1/me')->assertOk();
        $this->conToken($deOtraPersona)->getJson('/api/v1/me')->assertOk();
    }

    public function test_cerrar_las_demas_deja_abierta_solo_la_actual(): void
    {
        $usuario = $this->crearUsuario();
        $primera = $this->iniciarSesion($usuario);
        $segunda = $this->iniciarSesion($usuario);
        $actual = $this->iniciarSesion($usuario);

        $this->conToken($actual)->deleteJson('/api/v1/perfil/sesiones')->assertNoContent();

        $this->conToken($primera)->getJson('/api/v1/me')->assertUnauthorized();
        $this->conToken($segunda)->getJson('/api/v1/me')->assertUnauthorized();
        $this->conToken($actual)->getJson('/api/v1/me')->assertOk();
    }
}
