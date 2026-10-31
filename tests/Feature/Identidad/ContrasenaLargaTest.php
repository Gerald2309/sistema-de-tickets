<?php

namespace Tests\Feature\Identidad;

use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

// Se aceptan contraseñas de hasta 256 caracteres y se usan completas: con bcrypt, que ignora lo que pasa
// de 72 bytes, dos contraseñas distintas después de ese punto servirían por igual (OWASP, Password Storage).
class ContrasenaLargaTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->prepararDatosBase();
    }

    public function test_los_caracteres_despues_del_byte_72_cuentan(): void
    {
        $base = str_repeat('a', 80);
        $usuario = $this->crearUsuario('Solicitante', ['password' => $base.'X']);

        $this->sinCredencial()->postJson('/api/v1/auth/login', ['email' => $usuario->email, 'password' => $base.'Y'])->assertUnprocessable();
        $this->sinCredencial()->postJson('/api/v1/auth/login', ['email' => $usuario->email, 'password' => $base.'X'])->assertOk();
    }

    public function test_las_contrasenas_se_guardan_con_argon2id(): void
    {
        $this->assertSame('argon2id', config('hashing.driver'));
        $this->assertStringStartsWith('$argon2id$', $this->crearUsuario()->password);
        $this->assertStringStartsWith('$argon2id$', Hash::make('cualquier contraseña de prueba'));
    }
}
