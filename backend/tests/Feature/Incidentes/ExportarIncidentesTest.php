<?php

namespace Tests\Feature\Incidentes;

use App\Identidad\Dominio\ClavePermiso;
use App\Identidad\Models\Cliente;
use App\Identidad\Models\User;
use App\Incidentes\Dominio\Estado;
use OpenSpout\Reader\XLSX\Reader;
use Tests\TestCase;

class ExportarIncidentesTest extends TestCase
{
    private User $soporte;

    private string $token;

    protected function setUp(): void
    {
        parent::setUp();

        $this->prepararDatosBase();
        $this->freezeTime();
        $this->soporte = $this->usuarioConPermisos(ClavePermiso::VerTodos);
        $this->token = $this->iniciarSesion($this->soporte);
    }

    /**
     * Filas del CSV descargado, sin el BOM y con la primera fila (encabezados) aparte.
     *
     * @return array{list<string>, list<list<string>>}
     */
    private function csv(string $consulta, ?string $token = null): array
    {
        $respuesta = $this->conToken($token ?? $this->token)->get("/api/v1/incidentes/exportar?formato=csv{$consulta}")->assertOk();
        $contenido = $respuesta->streamedContent();

        $this->assertStringStartsWith("\xEF\xBB\xBF", $contenido, 'El CSV lleva BOM para que Excel lea bien las tildes.');
        $filas = array_map('str_getcsv', array_filter(explode("\n", substr($contenido, 3))));

        return [array_shift($filas), array_values($filas)];
    }

    public function test_descarga_un_csv_con_los_encabezados_y_los_datos_de_cada_incidente(): void
    {
        $empresa = Cliente::factory()->create(['nombre' => 'Constructora Andina']);
        $creador = $this->crearUsuario('Solicitante', ['name' => 'Marta Ruiz', 'cliente_id' => $empresa->id]);
        $asignado = $this->crearUsuario('Soporte N1', ['name' => 'Luis Pardo']);
        $incidente = $this->incidenteDe($creador, [
            'cliente_id' => $empresa->id,
            'titulo' => 'Sin internet, piso 3',
            'descripcion' => 'No hay conexión desde "esta mañana".',
            'usuario_asignado_id' => $asignado->id,
            'fecha_vencimiento' => now()->subDay(),
        ]);

        $respuesta = $this->conToken($this->token)->get('/api/v1/incidentes/exportar?formato=csv')->assertOk();
        $this->assertStringContainsString('text/csv', $respuesta->headers->get('Content-Type'));
        $this->assertStringContainsString('attachment; filename=incidentes-', $respuesta->headers->get('Content-Disposition'));
        $this->assertStringContainsString('.csv', $respuesta->headers->get('Content-Disposition'));

        [$encabezados, $filas] = $this->csv('');

        $this->assertSame('Código', $encabezados[0]);
        $this->assertCount(14, $encabezados);
        $this->assertSame('Empresa', $encabezados[9]);
        $this->assertCount(1, $filas);
        $this->assertSame(sprintf('INC-%06d', $incidente->id), $filas[0][0]);
        $this->assertSame('Sin internet, piso 3', $filas[0][1]);
        $this->assertSame('No hay conexión desde "esta mañana".', $filas[0][2]);
        $this->assertSame(['media', 'abierto', 'otros'], array_slice($filas[0], 3, 3));
        $this->assertSame(['Marta Ruiz', 'Luis Pardo', 'Constructora Andina'], array_slice($filas[0], 7, 3));
        $this->assertSame(['Sí'], [$filas[0][11]]);
    }

    public function test_aplica_los_mismos_filtros_y_orden_que_el_listado(): void
    {
        $creador = $this->crearUsuario();
        $abierto = $this->incidenteDe($creador);
        $this->incidenteDe($creador, ['estado' => Estado::EnProgreso]);
        $otroAbierto = $this->incidenteDe($creador);

        [, $filas] = $this->csv('&estado=abierto&direccion=asc');

        $this->assertSame([sprintf('INC-%06d', $abierto->id), sprintf('INC-%06d', $otroAbierto->id)], array_column($filas, 0));
    }

    public function test_exporta_solo_los_vencidos_si_se_piden(): void
    {
        $creador = $this->crearUsuario();
        $vencido = $this->incidenteDe($creador, ['fecha_vencimiento' => now()->subDay()]);
        $this->incidenteDe($creador, ['fecha_vencimiento' => now()->addDay()]);

        [, $filas] = $this->csv('&vencidos=1');

        $this->assertSame([sprintf('INC-%06d', $vencido->id)], array_column($filas, 0));
    }

    public function test_cada_persona_exporta_solo_lo_que_puede_ver(): void
    {
        $solicitante = $this->usuarioConPermisos(ClavePermiso::VerPropios);
        $propio = $this->incidenteDe($solicitante);
        $this->incidenteDe($this->crearUsuario());

        [, $filas] = $this->csv('', $this->iniciarSesion($solicitante));

        $this->assertSame([sprintf('INC-%06d', $propio->id)], array_column($filas, 0));
    }

    public function test_no_incluye_incidentes_borrados(): void
    {
        $creador = $this->crearUsuario();
        $this->incidenteDe($creador, ['deleted_at' => now()]);
        $visible = $this->incidenteDe($creador);

        [, $filas] = $this->csv('');

        $this->assertSame([sprintf('INC-%06d', $visible->id)], array_column($filas, 0));
    }

    public function test_los_textos_que_parecen_formulas_salen_como_texto(): void
    {
        $this->incidenteDe($this->crearUsuario(), ['titulo' => '=HYPERLINK("http://malo.example","clic")', 'descripcion' => '@SUM(A1:A9)']);

        [, $filas] = $this->csv('');

        $this->assertSame('\'=HYPERLINK("http://malo.example","clic")', $filas[0][1]);
        $this->assertSame("'@SUM(A1:A9)", $filas[0][2]);
    }

    public function test_descarga_un_excel_que_se_puede_leer_de_vuelta(): void
    {
        $incidente = $this->incidenteDe($this->crearUsuario('Solicitante', ['name' => 'Marta Ruiz']), ['titulo' => 'Impresora sin tóner']);

        $respuesta = $this->conToken($this->token)->get('/api/v1/incidentes/exportar?formato=xlsx')->assertOk();
        $this->assertStringContainsString('spreadsheetml.sheet', $respuesta->headers->get('Content-Type'));
        $this->assertStringContainsString('.xlsx', $respuesta->headers->get('Content-Disposition'));

        $archivo = tempnam(sys_get_temp_dir(), 'xlsx');
        file_put_contents($archivo, $respuesta->streamedContent());

        $lector = new Reader;
        $lector->open($archivo);
        $filas = [];
        foreach ($lector->getSheetIterator() as $hoja) {
            foreach ($hoja->getRowIterator() as $fila) {
                $filas[] = $fila->toArray();
            }
        }
        $lector->close();
        unlink($archivo);

        $this->assertCount(2, $filas);
        $this->assertSame('Código', $filas[0][0]);
        $this->assertSame(sprintf('INC-%06d', $incidente->id), $filas[1][0]);
        $this->assertSame('Impresora sin tóner', $filas[1][1]);
        $this->assertSame('Marta Ruiz', $filas[1][7]);
    }

    public function test_con_mas_incidentes_que_el_maximo_se_rechaza_sin_descargar_nada(): void
    {
        config(['incidentes.exportacion.maximo' => 2]);
        $creador = $this->crearUsuario();
        $this->incidenteDe($creador);
        $this->incidenteDe($creador);
        $this->conToken($this->token)->get('/api/v1/incidentes/exportar?formato=csv')->assertOk();

        $this->incidenteDe($creador);
        $this->conToken($this->token)->getJson('/api/v1/incidentes/exportar?formato=csv')
            ->assertUnprocessable()->assertJsonValidationErrors(['exportacion'], 'errors');
    }

    public function test_el_formato_es_obligatorio_y_se_valida(): void
    {
        $this->conToken($this->token)->getJson('/api/v1/incidentes/exportar')->assertUnprocessable()->assertJsonValidationErrors(['formato'], 'errors');
        $this->conToken($this->token)->getJson('/api/v1/incidentes/exportar?formato=pdf')->assertUnprocessable();
        $this->conToken($this->token)->getJson('/api/v1/incidentes/exportar?formato=csv&estado=volar')->assertUnprocessable();
    }

    public function test_exige_poder_ver_incidentes_y_credencial(): void
    {
        $sinPermiso = $this->iniciarSesion($this->usuarioConPermisos(ClavePermiso::CrearIncidente));

        $this->conToken($sinPermiso)->getJson('/api/v1/incidentes/exportar?formato=csv')->assertForbidden();
        $this->sinCredencial()->getJson('/api/v1/incidentes/exportar?formato=csv')->assertUnauthorized();
    }

    public function test_la_descarga_no_se_guarda_en_cache(): void
    {
        $this->conToken($this->token)->get('/api/v1/incidentes/exportar?formato=csv')
            ->assertHeader('Cache-Control', 'no-store, private')
            ->assertHeader('X-Content-Type-Options', 'nosniff');
    }
}
