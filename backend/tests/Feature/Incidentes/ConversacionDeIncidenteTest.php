<?php

namespace Tests\Feature\Incidentes;

use App\Identidad\Models\User;
use App\Incidentes\Acciones\EscribirMensaje;
use App\Incidentes\Dominio\Estado;
use App\Incidentes\Eventos\IncidentesCambiados;
use App\Incidentes\Models\AdjuntoMensaje;
use App\Incidentes\Models\Incidente;
use App\Incidentes\Models\MensajeIncidente;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ConversacionDeIncidenteTest extends TestCase
{
    // PNG válido de 1 × 1 píxel: el tipo se detecta por el contenido, así que la prueba necesita uno real.
    private const PNG = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==';

    private User $solicitante;

    private User $soporte;

    private Incidente $incidente;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake(EscribirMensaje::DISCO);
        $this->solicitante = $this->crearUsuario();
        $this->soporte = $this->crearUsuario('Soporte N1');
        $this->incidente = $this->incidenteDe($this->solicitante, ['usuario_asignado_id' => $this->soporte->id]);
    }

    private function ruta(?Incidente $incidente = null): string
    {
        return '/api/v1/incidentes/'.($incidente ?? $this->incidente)->id.'/mensajes';
    }

    /**
     * Archivo subido de verdad: su tipo se detecta por el contenido, como en producción. Los de
     * UploadedFile::fake() lo deducen de la extensión del nombre y dejarían pasar un disfraz.
     */
    private static function archivo(string $nombre, string $contenido): UploadedFile
    {
        $ruta = tempnam(sys_get_temp_dir(), 'adjunto');
        file_put_contents($ruta, $contenido);

        return new UploadedFile($ruta, $nombre, test: true);
    }

    public static function png(string $nombre = 'captura.png', int $bytesDeRelleno = 0): UploadedFile
    {
        return self::archivo($nombre, base64_decode(self::PNG).str_repeat("\0", $bytesDeRelleno));
    }

    public function test_el_solicitante_escribe_con_una_imagen_y_quien_lo_atiende_lo_lee(): void
    {
        $id = $this->conToken($this->iniciarSesion($this->solicitante))
            ->post($this->ruta(), ['cuerpo' => 'Adjunto el error que me sale.', 'imagenes' => [self::png()]], ['Accept' => 'application/json'])
            ->assertCreated()
            ->assertJsonPath('data.cuerpo', 'Adjunto el error que me sale.')
            ->assertJsonPath('data.interno', false)
            ->assertJsonPath('data.autor.id', $this->solicitante->id)
            ->assertJsonPath('data.adjuntos.0.nombre', 'captura.png')
            ->assertJsonPath('data.adjuntos.0.tipo', 'image/png')
            ->json('data.id');

        $adjunto = AdjuntoMensaje::sole();
        Storage::disk(EscribirMensaje::DISCO)->assertExists($adjunto->ruta);
        $this->assertStringStartsWith("incidentes/{$this->incidente->id}/", $adjunto->ruta);
        $this->assertStringNotContainsString('captura', $adjunto->ruta, 'El archivo se guarda con un nombre aleatorio.');

        $token = $this->iniciarSesion($this->soporte);
        $this->conToken($token)->getJson($this->ruta())->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $id);

        $imagen = $this->conToken($token)->get("{$this->ruta()}/{$id}/adjuntos/{$adjunto->id}")->assertOk();
        $this->assertSame('image/png', $imagen->headers->get('Content-Type'));
        $this->assertSame('nosniff', $imagen->headers->get('X-Content-Type-Options'));
        $this->assertSame(base64_decode(self::PNG), $imagen->streamedContent());
    }

    public function test_la_conversacion_sale_de_lo_mas_reciente_a_lo_anterior_y_escribir_avisa_en_tiempo_real(): void
    {
        Event::fake([IncidentesCambiados::class]);

        $this->conToken($this->iniciarSesion($this->solicitante))->postJson($this->ruta(), ['cuerpo' => 'No imprime.'])->assertCreated();
        $this->conToken($this->iniciarSesion($this->soporte))->postJson($this->ruta(), ['cuerpo' => '¿Está encendida?'])->assertCreated();

        $respuesta = $this->conToken($this->iniciarSesion($this->solicitante))->getJson($this->ruta())->assertOk();
        $this->assertSame(['¿Está encendida?', 'No imprime.'], array_column($respuesta->json('data'), 'cuerpo'));
        Event::assertDispatchedTimes(IncidentesCambiados::class, 2);
    }

    public function test_las_notas_internas_las_escribe_quien_atiende_y_el_solicitante_nunca_las_ve(): void
    {
        $tokenDeSoporte = $this->iniciarSesion($this->soporte);
        $this->conToken($tokenDeSoporte)->postJson($this->ruta(), ['cuerpo' => 'Respuesta al solicitante.'])->assertCreated();
        $nota = $this->conToken($tokenDeSoporte)
            ->post($this->ruta(), ['cuerpo' => 'Parece el controlador.', 'interno' => '1', 'imagenes' => [self::png()]], ['Accept' => 'application/json'])
            ->assertCreated()
            ->assertJsonPath('data.interno', true)
            ->json('data');

        $tokenDelSolicitante = $this->iniciarSesion($this->solicitante);
        $this->conToken($tokenDelSolicitante)->getJson($this->ruta())->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.cuerpo', 'Respuesta al solicitante.');
        $this->conToken($tokenDelSolicitante)->getJson("{$this->ruta()}/{$nota['id']}/adjuntos/{$nota['adjuntos'][0]['id']}")->assertNotFound();

        $this->conToken($this->iniciarSesion($this->crearUsuario('Soporte N2')))->getJson($this->ruta())->assertOk()->assertJsonCount(2, 'data');
    }

    public function test_la_cantidad_de_consultas_no_depende_de_cuantos_mensajes_e_imagenes_haya(): void
    {
        $token = $this->iniciarSesion($this->soporte);
        // Sanctum actualiza la fecha de último uso del token cuando cambia el segundo: con el reloj quieto y
        // una petición previa, esa consulta no entra en la comparación.
        $this->freezeTime();
        $this->conToken($token)->getJson($this->ruta())->assertOk();

        $consultas = function (int $cantidad) use ($token): int {
            collect(range(1, $cantidad))->each(function (int $n) {
                $mensaje = $this->incidente->mensajes()->create(['usuario_id' => $n % 2 ? $this->soporte->id : $this->solicitante->id, 'cuerpo' => "Mensaje {$n}"]);
                $mensaje->adjuntos()->create(['ruta' => "incidentes/{$mensaje->id}.png", 'nombre' => 'captura.png', 'tipo' => 'image/png', 'bytes' => 68]);
            });
            DB::flushQueryLog();
            DB::enableQueryLog();
            $this->conToken($token)->getJson($this->ruta())->assertOk();

            return count(DB::getQueryLog());
        };

        $this->assertSame($consultas(2), $consultas(20), 'Más mensajes no debe significar más consultas (N+1).');
    }

    public function test_el_solicitante_no_escribe_notas_internas(): void
    {
        $this->conToken($this->iniciarSesion($this->solicitante))->postJson($this->ruta(), ['cuerpo' => 'Hola', 'interno' => true])->assertForbidden();

        $this->assertSame(0, MensajeIncidente::count());
    }

    public function test_el_resto_de_soporte_lee_la_conversacion_pero_no_escribe(): void
    {
        $token = $this->iniciarSesion($this->crearUsuario('Soporte N2'));

        $this->conToken($token)->getJson($this->ruta())->assertOk();
        $this->conToken($token)->postJson($this->ruta(), ['cuerpo' => 'Hola'])->assertForbidden();
        $this->conToken($this->iniciarSesion($this->crearUsuario('Administrador')))->postJson($this->ruta(), ['cuerpo' => 'Hola'])->assertForbidden();
    }

    public function test_un_incidente_ajeno_responde_404_al_leer_y_al_escribir(): void
    {
        $token = $this->iniciarSesion($this->crearUsuario());

        $this->conToken($token)->getJson($this->ruta())->assertNotFound();
        $this->conToken($token)->postJson($this->ruta(), ['cuerpo' => 'Hola'])->assertNotFound();
    }

    public function test_un_incidente_cerrado_ya_no_admite_mensajes_pero_uno_resuelto_si(): void
    {
        $token = $this->iniciarSesion($this->solicitante);
        $resuelto = $this->incidenteDe($this->solicitante, ['estado' => Estado::Resuelto, 'solucion' => 'Listo.']);
        $cerrado = $this->incidenteDe($this->solicitante, ['estado' => Estado::Cerrado, 'solucion' => 'Listo.']);

        $this->conToken($token)->postJson($this->ruta($resuelto), ['cuerpo' => 'Sigue fallando.'])->assertCreated();
        $this->conToken($token)->postJson($this->ruta($cerrado), ['cuerpo' => 'Sigue fallando.'])
            ->assertConflict()
            ->assertJsonPath('type', 'urn:incidentes:problema:conversacion-cerrada');
    }

    /**
     * @return array<string, array{array<string, mixed>, string}>
     */
    public static function mensajesInvalidos(): array
    {
        return [
            'sin texto ni imágenes' => [['cuerpo' => ''], 'cuerpo'],
            'texto demasiado largo' => [['cuerpo' => str_repeat('a', 5001)], 'cuerpo'],
            'código con extensión de imagen' => [['imagenes' => [self::archivo('foto.png', '<?php echo 1;')]], 'imagenes.0'],
            'SVG, que puede llevar código' => [['imagenes' => [self::archivo('dibujo.svg', '<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg>')]], 'imagenes.0'],
            'imagen de más de 5 MB' => [['imagenes' => [self::png('grande.png', 5 * 1024 * 1024)]], 'imagenes.0'],
            'más de 4 imágenes' => [['imagenes' => array_map(fn () => self::png(), range(1, 5))], 'imagenes'],
        ];
    }

    /**
     * @param  array<string, mixed>  $cuerpo
     */
    #[DataProvider('mensajesInvalidos')]
    public function test_rechaza_mensajes_invalidos_sin_guardar_nada(array $cuerpo, string $campo): void
    {
        $this->conToken($this->iniciarSesion($this->solicitante))
            ->post($this->ruta(), $cuerpo, ['Accept' => 'application/json'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors($campo);

        $this->assertSame(0, MensajeIncidente::count());
        $this->assertSame([], Storage::disk(EscribirMensaje::DISCO)->allFiles());
    }

    public function test_un_adjunto_solo_se_alcanza_desde_su_mensaje_y_su_incidente(): void
    {
        $token = $this->iniciarSesion($this->solicitante);
        $mensaje = $this->conToken($token)
            ->post($this->ruta(), ['imagenes' => [self::png()]], ['Accept' => 'application/json'])
            ->assertCreated()
            ->json('data');
        $otro = $this->incidenteDe($this->solicitante);

        $this->conToken($token)->get("{$this->ruta($otro)}/{$mensaje['id']}/adjuntos/{$mensaje['adjuntos'][0]['id']}")->assertNotFound();
    }
}
