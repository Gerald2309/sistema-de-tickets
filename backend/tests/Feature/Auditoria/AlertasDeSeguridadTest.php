<?php

namespace Tests\Feature\Auditoria;

use App\Auditoria\Dominio\AccionAuditoria;
use App\Auditoria\Models\Auditoria;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class AlertasDeSeguridadTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config(['seguridad.alertas' => ['ventana_minutos' => 15, 'fallidos_por_cuenta' => 3, 'fallidos_por_ip' => 6]]);
        Log::spy();
    }

    private function fallar(string $correo, int $veces): void
    {
        for ($i = 0; $i < $veces; $i++) {
            $this->postJson('/api/v1/auth/login', ['email' => $correo, 'password' => 'no es la contraseña'])->assertUnprocessable();
        }
    }

    /**
     * @return list<array{usuario_afectado_id: int|null, detalle: string|null}>
     */
    private function alertas(): array
    {
        return Auditoria::where('accion', AccionAuditoria::AlertaDeSeguridad)->get(['usuario_afectado_id', 'detalle'])->toArray();
    }

    public function test_varios_fallos_sobre_una_cuenta_dejan_una_sola_alerta_y_un_aviso_en_el_log(): void
    {
        $persona = $this->crearUsuario();

        $this->fallar($persona->email, 2);
        $this->assertSame([], $this->alertas(), 'Por debajo del umbral no se alerta.');

        $this->fallar($persona->email, 2);
        $this->assertSame([['usuario_afectado_id' => $persona->id, 'detalle' => '3 inicios fallidos sobre esta cuenta en 15 minutos']], $this->alertas());
        Log::shouldHaveReceived('warning')->once()->withArgs(fn (string $mensaje) => str_contains($mensaje, 'Alerta de seguridad'));
    }

    public function test_muchos_fallos_desde_una_ip_sobre_cuentas_distintas_alertan_por_la_ip(): void
    {
        // Ninguna cuenta llega a su umbral; la IP sí.
        $this->fallar($this->crearUsuario()->email, 2);
        $this->fallar($this->crearUsuario()->email, 2);
        $this->fallar('nadie@empresa-ejemplo.com', 2);

        $this->assertSame([['usuario_afectado_id' => null, 'detalle' => '6 inicios fallidos desde esta IP en 15 minutos']], $this->alertas());
    }

    public function test_pasada_la_ventana_se_vuelve_a_alertar(): void
    {
        $persona = $this->crearUsuario();
        $this->fallar($persona->email, 3);

        $this->travel(16)->minutes();
        $this->fallar($persona->email, 3);

        $this->assertCount(2, array_filter($this->alertas(), fn (array $alerta) => $alerta['usuario_afectado_id'] === $persona->id));
    }

    public function test_quien_audita_ve_las_alertas_y_puede_filtrarlas(): void
    {
        $this->fallar($this->crearUsuario()->email, 3);

        $this->conToken($this->iniciarSesion($this->crearUsuario('Administrador')))
            ->getJson('/api/v1/auditoria?accion=alerta_de_seguridad')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.accion', 'alerta_de_seguridad');
    }
}
