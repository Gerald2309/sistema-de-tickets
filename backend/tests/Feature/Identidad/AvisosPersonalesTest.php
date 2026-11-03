<?php

namespace Tests\Feature\Identidad;

use App\Identidad\Models\User;
use App\Incidentes\Notificaciones\AvisoDeIncidente;
use Tests\TestCase;

class AvisosPersonalesTest extends TestCase
{
    private User $persona;

    private string $token;

    protected function setUp(): void
    {
        parent::setUp();

        $this->prepararDatosBase();
        $this->persona = $this->crearUsuario('Soporte N2');
        $this->token = $this->iniciarSesion($this->persona);
    }

    public function test_cada_quien_ve_solo_sus_avisos_y_cuantos_le_faltan_por_leer(): void
    {
        $this->persona->notify(new AvisoDeIncidente(42, '«Red a N2»: INC-000042 · Sin internet'));
        $this->crearUsuario('Soporte N2')->notify(new AvisoDeIncidente(7, 'De otra persona'));

        $this->conToken($this->token)->getJson('/api/v1/perfil/avisos')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.texto', '«Red a N2»: INC-000042 · Sin internet')
            ->assertJsonPath('data.0.incidente_id', 42)
            ->assertJsonPath('data.0.leido', false)
            ->assertJsonPath('meta.sin_leer', 1);
    }

    public function test_marca_uno_o_todos_como_leidos(): void
    {
        $this->persona->notify(new AvisoDeIncidente(1, 'Uno'));
        $this->persona->notify(new AvisoDeIncidente(2, 'Dos'));
        $primero = $this->persona->notifications()->firstOrFail();

        $this->conToken($this->token)->patchJson("/api/v1/perfil/avisos/{$primero->id}")->assertNoContent();
        $this->assertSame(1, $this->persona->unreadNotifications()->count());

        $this->conToken($this->token)->postJson('/api/v1/perfil/avisos/leidos')->assertNoContent();
        $this->assertSame(0, $this->persona->unreadNotifications()->count());
    }

    public function test_no_puede_marcar_el_aviso_de_otra_persona(): void
    {
        $otra = $this->crearUsuario('Soporte N2');
        $otra->notify(new AvisoDeIncidente(1, 'De otra persona'));

        $this->conToken($this->token)->patchJson("/api/v1/perfil/avisos/{$otra->notifications()->firstOrFail()->id}")->assertNotFound();
        $this->assertSame(1, $otra->unreadNotifications()->count());
    }
}
