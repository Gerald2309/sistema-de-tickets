<?php

namespace Tests\Feature\Incidentes;

use App\Identidad\Models\User;
use App\Incidentes\Dominio\Categoria;
use App\Incidentes\Dominio\CodigoIncidente;
use App\Incidentes\Dominio\Estado;
use App\Incidentes\Models\Incidente;
use Illuminate\Testing\TestResponse;
use Tests\TestCaseConDatosConfirmados;

// Los parecidos usan el índice de texto de MySQL, que solo ve datos confirmados.
class IncidentesSimilaresTest extends TestCaseConDatosConfirmados
{
    private User $soporte;

    private string $token;

    protected function setUp(): void
    {
        parent::setUp();

        $this->soporte = $this->crearUsuario('Soporte N1');
        $this->token = $this->iniciarSesion($this->soporte);
    }

    private function similares(Incidente $incidente, ?string $token = null): TestResponse
    {
        return $this->conToken($token ?? $this->token)->getJson("/api/v1/incidentes/{$incidente->id}/similares");
    }

    /**
     * @param  array<string, mixed>  $atributos
     */
    private function resuelto(string $titulo, string $descripcion, array $atributos = []): Incidente
    {
        return $this->incidenteDe($this->soporte, $atributos + [
            'titulo' => $titulo,
            'descripcion' => $descripcion,
            'categoria' => Categoria::Red,
            'estado' => Estado::Resuelto,
            'solucion' => "Solución de «{$titulo}».",
        ]);
    }

    private function actual(): Incidente
    {
        return $this->incidenteDe($this->crearUsuario(), [
            'titulo' => 'No puedo conectarme a la VPN',
            'descripcion' => 'La VPN rechaza mis credenciales con el error 691.',
            'categoria' => Categoria::Red,
        ]);
    }

    public function test_devuelve_los_casos_resueltos_del_mas_al_menos_parecido_con_su_solucion(): void
    {
        $actual = $this->actual();
        $menos = $this->resuelto('La VPN va lenta por las tardes', 'Tarda en abrir las carpetas.');
        $mas = $this->resuelto('La VPN rechaza credenciales error 691', 'No conecta desde casa.');

        $respuesta = $this->similares($actual)
            ->assertOk()
            ->assertJsonCount(2, 'data');

        $this->assertSame([$mas->id, $menos->id], array_column($respuesta->json('data'), 'id'));
        $this->assertSame([
            'id' => $mas->id,
            'codigo' => CodigoIncidente::deId($mas->id),
            'titulo' => 'La VPN rechaza credenciales error 691',
            'categoria' => 'red',
            'estado' => 'resuelto',
            'solucion' => 'Solución de «La VPN rechaza credenciales error 691».',
        ], $respuesta->json('data.0'));
    }

    public function test_incluye_los_cerrados_y_excluye_lo_que_no_sirve_como_precedente(): void
    {
        $actual = $this->actual();
        $cerrado = $this->resuelto('VPN con error 691 resuelta', 'Credenciales rechazadas.', ['estado' => Estado::Cerrado]);
        $this->resuelto('VPN abierta con error 691', 'Credenciales rechazadas.', ['estado' => Estado::Abierto, 'solucion' => null]);
        $this->resuelto('VPN en progreso con error 691', 'Credenciales rechazadas.', ['estado' => Estado::EnProgreso, 'solucion' => null]);
        $this->resuelto('VPN de otra categoría con error 691', 'Credenciales rechazadas.', ['categoria' => Categoria::Seguridad]);
        $this->resuelto('VPN borrada con error 691', 'Credenciales rechazadas.')->delete();

        $this->assertSame([$cerrado->id], array_column($this->similares($actual)->json('data'), 'id'));
    }

    public function test_el_propio_incidente_no_aparece_como_parecido_de_si_mismo(): void
    {
        $actual = $this->resuelto('La VPN rechaza credenciales error 691', 'No conecta desde casa.');

        $this->similares($actual)->assertOk()->assertExactJson(['data' => []]);
    }

    public function test_devuelve_como_maximo_cinco(): void
    {
        $actual = $this->actual();
        collect(range(1, 8))->each(fn (int $n) => $this->resuelto("VPN con error 691 caso $n", 'Credenciales rechazadas.'));

        $this->similares($actual)->assertOk()->assertJsonCount(5, 'data');
    }

    public function test_las_palabras_muy_comunes_no_hacen_parecidos_a_dos_incidentes(): void
    {
        $actual = $this->incidenteDe($this->crearUsuario(), [
            'titulo' => 'Solicitud para instalar Acrobat',
            'descripcion' => 'Necesito que desde hoy pueda editar PDF.',
            'categoria' => Categoria::Red,
        ]);
        // Solo comparte «para», «que» y «desde», que no cuentan.
        $this->resuelto('Teclado para que desde el escritorio funcione', 'Se cambió el receptor.');
        $parecido = $this->resuelto('Instalar Acrobat en el equipo', 'Licencia asignada.');

        $this->assertSame([$parecido->id], array_column($this->similares($actual)->json('data'), 'id'));
    }

    public function test_sin_palabras_utiles_responde_una_lista_vacia_sin_error(): void
    {
        $actual = $this->incidenteDe($this->crearUsuario(), ['titulo' => 'Para que desde todo', 'descripcion' => 'Esto es una de las otras.']);
        $this->resuelto('Cualquier otro caso resuelto', 'Con su descripción.');

        $this->similares($actual)->assertOk()->assertExactJson(['data' => []]);
    }

    public function test_sin_casos_parecidos_devuelve_una_lista_vacia(): void
    {
        $this->resuelto('Impresora sin tinta', 'Hay que cambiar el cartucho.');

        $this->similares($this->actual())->assertOk()->assertExactJson(['data' => []]);
    }

    public function test_no_expone_datos_de_las_personas(): void
    {
        $actual = $this->actual();
        $this->resuelto('La VPN rechaza credenciales error 691', 'No conecta.', ['usuario_asignado_id' => $this->soporte->id]);

        $campos = array_keys($this->similares($actual)->json('data.0'));

        $this->assertSame(['id', 'codigo', 'titulo', 'categoria', 'estado', 'solucion'], $campos);
    }

    public function test_solo_quien_ve_todo_puede_pedir_parecidos(): void
    {
        $solicitante = $this->crearUsuario();
        $propio = $this->incidenteDe($solicitante, ['titulo' => 'No puedo conectarme a la VPN', 'descripcion' => 'Error 691.']);
        $ajeno = $this->actual();
        $tokenSolicitante = $this->iniciarSesion($solicitante);

        $this->similares($propio, $tokenSolicitante)->assertForbidden();
        $this->similares($ajeno, $tokenSolicitante)->assertNotFound();
        $this->similares($ajeno, $this->iniciarSesion($this->crearUsuario('Administrador')))->assertOk();
    }

    public function test_un_incidente_inexistente_o_borrado_responde_404_y_sin_token_401(): void
    {
        $borrado = $this->actual();
        $borrado->delete();

        $this->conToken($this->token)->getJson('/api/v1/incidentes/999999/similares')->assertNotFound();
        $this->similares($borrado)->assertNotFound();
        $this->sinCredencial()->getJson("/api/v1/incidentes/{$borrado->id}/similares")->assertUnauthorized();
    }
}
