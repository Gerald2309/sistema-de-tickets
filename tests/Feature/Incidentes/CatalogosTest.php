<?php

namespace Tests\Feature\Incidentes;

use Tests\TestCase;

class CatalogosTest extends TestCase
{
    public function test_devuelve_estados_con_sus_siguientes_prioridades_y_categorias(): void
    {
        $token = $this->iniciarSesion($this->crearUsuario());

        $this->conToken($token)->getJson('/api/v1/catalogos')
            ->assertOk()
            ->assertJsonPath('estados', [
                ['valor' => 'abierto', 'siguientes' => ['en_progreso']],
                ['valor' => 'en_progreso', 'siguientes' => ['resuelto']],
                ['valor' => 'resuelto', 'siguientes' => ['cerrado', 'en_progreso']],
                ['valor' => 'cerrado', 'siguientes' => []],
            ])
            ->assertJsonPath('prioridades', ['baja', 'media', 'alta', 'critica'])
            ->assertJsonPath('categorias', ['red', 'correo', 'hardware', 'software', 'accesos', 'seguridad', 'telefonia', 'otros']);
    }

    public function test_exige_haber_iniciado_sesion(): void
    {
        $this->getJson('/api/v1/catalogos')->assertUnauthorized();
    }
}
