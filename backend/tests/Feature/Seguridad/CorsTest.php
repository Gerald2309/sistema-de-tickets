<?php

namespace Tests\Feature\Seguridad;

use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class CorsTest extends TestCase
{
    private const ORIGEN = 'https://soporte.empresa-ejemplo.com';

    private function preflight(string $origen): TestResponse
    {
        return $this->withHeaders(['Origin' => $origen, 'Access-Control-Request-Method' => 'GET', 'Access-Control-Request-Headers' => 'authorization'])
            ->options('/api/v1/incidentes');
    }

    public function test_sin_origenes_configurados_no_se_abre_a_ningun_navegador(): void
    {
        $this->assertSame([], config('cors.allowed_origins'));

        $this->preflight(self::ORIGEN)->assertHeaderMissing('Access-Control-Allow-Origin');
    }

    public function test_un_origen_de_la_lista_puede_consultar_y_otro_no(): void
    {
        config(['cors.allowed_origins' => [self::ORIGEN]]);

        $this->preflight(self::ORIGEN)->assertHeader('Access-Control-Allow-Origin', self::ORIGEN);
        // Con un solo origen permitido la respuesta siempre nombra ese origen; el navegador rechaza al que no coincide.
        $otro = 'https://otro-sitio.example';
        $this->assertNotSame($otro, $this->preflight($otro)->headers->get('Access-Control-Allow-Origin'));
        $this->withHeaders(['Origin' => self::ORIGEN])->getJson('/api/v1/incidentes')->assertUnauthorized()->assertHeader('Access-Control-Allow-Origin', self::ORIGEN);
    }

    public function test_nunca_se_admiten_credenciales_ni_todos_los_origenes(): void
    {
        $this->assertFalse(config('cors.supports_credentials'));
        $this->assertNotContains('*', config('cors.allowed_origins'));
    }
}
