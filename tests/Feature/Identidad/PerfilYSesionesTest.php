<?php

namespace Tests\Feature\Identidad;

use App\Identidad\Dominio\ClavePermiso;
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
                'permisos' => ['crear_incidente', 'ver_todos'],
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
}
