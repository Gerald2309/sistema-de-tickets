<?php

namespace Tests\Feature\Incidentes;

use App\Incidentes\Dominio\Estado;
use App\Incidentes\Dominio\Prioridad;
use Illuminate\Testing\TestResponse;
use Tests\TestCaseConDatosConfirmados;

// La búsqueda usa el índice de texto de MySQL, que solo ve datos confirmados: por eso estas pruebas
// no corren dentro de una transacción (TestCaseConDatosConfirmados).
class BuscarIncidentesTest extends TestCaseConDatosConfirmados
{
    private function buscar(string $token, string $texto, array $otros = []): TestResponse
    {
        return $this->conToken($token)->getJson('/api/v1/incidentes?'.http_build_query(['q' => $texto] + $otros));
    }

    /**
     * @return list<int>
     */
    private function ids(TestResponse $respuesta): array
    {
        return array_column($respuesta->json('data'), 'id');
    }

    public function test_encuentra_por_una_palabra_del_titulo_o_de_la_descripcion(): void
    {
        $soporte = $this->crearUsuario('Soporte N1');
        $vpn = $this->incidenteDe($soporte, ['titulo' => 'No puedo conectarme a la VPN', 'descripcion' => 'Falla desde ayer.']);
        $impresora = $this->incidenteDe($soporte, ['titulo' => 'Problema de impresión', 'descripcion' => 'La impresora del segundo piso no responde.']);
        $token = $this->iniciarSesion($soporte);

        $this->assertSame([$vpn->id], $this->ids($this->buscar($token, 'vpn')));
        $this->assertSame([$impresora->id], $this->ids($this->buscar($token, 'impresora')));
        $this->assertSame([], $this->ids($this->buscar($token, 'inexistente')));
    }

    public function test_varias_palabras_se_exigen_todas(): void
    {
        $soporte = $this->crearUsuario('Soporte N1');
        $ambas = $this->incidenteDe($soporte, ['titulo' => 'Error de conexión en el ERP', 'descripcion' => 'Compras no puede emitir pedidos.']);
        $this->incidenteDe($soporte, ['titulo' => 'Error de impresión', 'descripcion' => 'La impresora no responde.']);
        $this->incidenteDe($soporte, ['titulo' => 'Conexión lenta', 'descripcion' => 'La red va despacio.']);
        $token = $this->iniciarSesion($soporte);

        $this->assertSame([$ambas->id], $this->ids($this->buscar($token, 'error conexión')));
        $this->assertCount(2, $this->ids($this->buscar($token, 'error')));
        $this->assertCount(2, $this->ids($this->buscar($token, 'conexión')));
    }

    public function test_no_distingue_mayusculas_ni_acentos(): void
    {
        $soporte = $this->crearUsuario('Soporte N1');
        $incidente = $this->incidenteDe($soporte, ['titulo' => 'Falla de conexión', 'descripcion' => 'Sin servicio.']);
        $token = $this->iniciarSesion($soporte);

        $this->assertSame([$incidente->id], $this->ids($this->buscar($token, 'CONEXIÓN')));
        $this->assertSame([$incidente->id], $this->ids($this->buscar($token, 'conexion')));
    }

    public function test_las_palabras_de_menos_de_tres_letras_se_ignoran(): void
    {
        $soporte = $this->crearUsuario('Soporte N1');
        $incidente = $this->incidenteDe($soporte, ['titulo' => 'No puedo conectarme a la VPN', 'descripcion' => 'Falla.']);
        $token = $this->iniciarSesion($soporte);

        $this->assertSame([$incidente->id], $this->ids($this->buscar($token, 'vpn de la')));
    }

    public function test_si_no_queda_ninguna_palabra_util_responde_422_con_un_mensaje(): void
    {
        $token = $this->iniciarSesion($this->crearUsuario('Soporte N1'));

        foreach (['a la de', '+-*"()', '  '] as $texto) {
            $respuesta = $this->buscar($token, $texto);

            if (trim($texto) === '') {
                // Un texto en blanco no es una búsqueda: se ignora y se lista todo.
                $respuesta->assertOk();

                continue;
            }

            $respuesta->assertUnprocessable()
                ->assertJsonValidationErrors('q')
                ->assertJsonPath('errors.q.0', 'La búsqueda debe incluir al menos una palabra de 3 o más letras o números.');
        }
    }

    public function test_los_operadores_del_motor_no_alteran_la_busqueda(): void
    {
        $soporte = $this->crearUsuario('Soporte N1');
        $incidente = $this->incidenteDe($soporte, ['titulo' => 'La VPN no conecta', 'descripcion' => 'Desde casa.']);
        $this->incidenteDe($soporte, ['titulo' => 'Impresora sin tinta', 'descripcion' => 'Segundo piso.']);
        $token = $this->iniciarSesion($soporte);

        // "-impresora" y "*" no excluyen ni expanden nada: solo cuentan las palabras.
        $this->assertSame([], $this->ids($this->buscar($token, '+vpn -impresora*')));
        $this->assertSame([$incidente->id], $this->ids($this->buscar($token, '"vpn" (casa)')));
    }

    public function test_solo_se_combinan_las_primeras_cinco_palabras(): void
    {
        $soporte = $this->crearUsuario('Soporte N1');
        $incidente = $this->incidenteDe($soporte, ['titulo' => 'alfa bravo charlie delta echo', 'descripcion' => 'Texto sin la sexta palabra.']);
        $token = $this->iniciarSesion($soporte);

        $this->assertSame([$incidente->id], $this->ids($this->buscar($token, 'alfa bravo charlie delta echo foxtrot')));
    }

    public function test_la_busqueda_respeta_la_visibilidad_y_se_combina_con_los_filtros(): void
    {
        $ana = $this->crearUsuario();
        $luis = $this->crearUsuario();
        $deAna = $this->incidenteDe($ana, ['titulo' => 'La VPN no conecta', 'descripcion' => 'Desde casa.', 'prioridad' => Prioridad::Alta]);
        $this->incidenteDe($ana, ['titulo' => 'La VPN va lenta', 'descripcion' => 'Por la tarde.', 'prioridad' => Prioridad::Baja]);
        $this->incidenteDe($luis, ['titulo' => 'La VPN se cae', 'descripcion' => 'Cada hora.', 'prioridad' => Prioridad::Alta]);

        $this->assertCount(2, $this->ids($this->buscar($this->iniciarSesion($ana), 'vpn')));
        $this->assertSame([$deAna->id], $this->ids($this->buscar($this->iniciarSesion($ana), 'vpn', ['prioridad' => 'alta'])));
        $this->assertCount(3, $this->ids($this->buscar($this->iniciarSesion($this->crearUsuario('Soporte N1')), 'vpn')));
    }

    public function test_no_encuentra_los_incidentes_borrados(): void
    {
        $soporte = $this->crearUsuario('Soporte N1');
        $incidente = $this->incidenteDe($soporte, ['titulo' => 'Incidente borrado de prueba', 'descripcion' => 'Ya no existe.']);
        $incidente->delete();

        $this->assertSame([], $this->ids($this->buscar($this->iniciarSesion($soporte), 'borrado')));
    }

    public function test_la_busqueda_se_pagina_con_el_total_de_coincidencias(): void
    {
        $soporte = $this->crearUsuario('Soporte N1');
        collect(range(1, 8))->each(fn () => $this->incidenteDe($soporte, ['titulo' => 'Falla de la red interna', 'descripcion' => 'Afecta a todas las áreas.', 'estado' => Estado::Abierto]));
        $this->incidenteDe($soporte, ['titulo' => 'Otro tema distinto', 'descripcion' => 'No se parece.']);

        $this->buscar($this->iniciarSesion($soporte), 'red', ['per_page' => 3])
            ->assertOk()
            ->assertJsonCount(3, 'data')
            ->assertJsonPath('meta.total', 8)
            ->assertJsonPath('meta.last_page', 3);
    }
}
