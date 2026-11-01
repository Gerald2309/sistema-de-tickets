<?php

namespace Tests\Feature\Identidad;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ReglaDeContrasenaTest extends TestCase
{
    private function esValida(string $contrasena): bool
    {
        return Validator::make(['password' => $contrasena], ['password' => [Password::defaults()]])->passes();
    }

    /**
     * @return array<string, array{string}>
     */
    public static function incumplidas(): array
    {
        return [
            'muy corta' => ['Ab1-corta'],
            'sin mayúscula' => ['una-clave-larga-2026'],
            'sin minúscula' => ['UNA-CLAVE-LARGA-2026'],
            'sin número' => ['Una-Clave-Bien-Larga'],
            'sin símbolo' => ['UnaClaveBienLarga2026'],
            'demasiado larga' => ['Ab1-'.str_repeat('x', 253)],
        ];
    }

    #[DataProvider('incumplidas')]
    public function test_rechaza_lo_que_no_cumple_la_regla(string $contrasena): void
    {
        $this->assertFalse($this->esValida($contrasena));
    }

    public function test_acepta_la_contrasena_de_demostracion_y_una_frase_con_espacios(): void
    {
        $this->assertTrue($this->esValida('Psicol-Mesa-De-Ayuda-2026'));
        $this->assertTrue($this->esValida('Mi contraseña Ñandú 2026'));
    }

    public function test_rechaza_una_contrasena_filtrada_sin_enviarla_y_explica_por_que(): void
    {
        config(['seguridad.contrasenas.comprobar_filtradas' => true]);
        $contrasena = 'Psicol-Mesa-De-Ayuda-2026';
        $hash = strtoupper(sha1($contrasena));
        Http::fake(['api.pwnedpasswords.com/*' => Http::response(substr($hash, 5).":3\r\n")]);

        $validador = Validator::make(['password' => $contrasena], ['password' => [Password::defaults()]]);

        $this->assertTrue($validador->fails());
        $this->assertStringContainsString('filtración', (string) $validador->errors()->first('password'));
        Http::assertSent(fn ($peticion) => str_ends_with($peticion->url(), '/range/'.substr($hash, 0, 5)));
    }

    public function test_si_el_servicio_de_filtraciones_no_responde_no_impide_guardar(): void
    {
        config(['seguridad.contrasenas.comprobar_filtradas' => true]);
        Http::fake(['api.pwnedpasswords.com/*' => Http::response('', 500)]);

        $this->assertTrue($this->esValida('Psicol-Mesa-De-Ayuda-2026'));
    }
}
