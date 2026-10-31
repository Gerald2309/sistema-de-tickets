<?php

namespace Tests\Rendimiento;

use App\Identidad\Models\Rol;
use App\Identidad\Models\User;
use Illuminate\Support\Facades\DB;

class EscenariosDeRendimientoTest extends PruebaDeRendimiento
{
    private string $tokenSolicitante;

    protected function setUp(): void
    {
        parent::setUp();

        $solicitante = User::where('rol_id', Rol::where('nombre', 'Solicitante')->value('id'))->orderBy('id')->firstOrFail();
        $this->tokenSolicitante = $solicitante->createToken('rendimiento')->plainTextToken;
    }

    public function test_listado_sin_filtros_primera_pagina(): void
    {
        $this->medirPeticion('listado sin filtros, página 1', $this->tokenAdministrador, '/api/v1/incidentes', conMeta: true);
    }

    public function test_listado_con_filtros_y_orden(): void
    {
        $asignado = DB::table('incidentes')->whereNotNull('usuario_asignado_id')->value('usuario_asignado_id');
        $consulta = http_build_query([
            'estado' => 'abierto',
            'prioridad' => 'alta',
            'usuario_asignado_id' => $asignado,
            'fecha_desde' => now()->subDays(30)->format('Y-m-d\TH:i:s\Z'),
            'fecha_hasta' => now()->addDays(30)->format('Y-m-d\TH:i:s\Z'),
            'ordenar_por' => 'fecha_vencimiento',
            'direccion' => 'asc',
        ]);

        $this->medirPeticion('listado con estado, prioridad, asignado, fechas y orden', $this->tokenAdministrador, "/api/v1/incidentes?{$consulta}", conMeta: true);
    }

    public function test_busqueda_con_una_palabra_poco_frecuente(): void
    {
        // El número de equipo aparece en unas pocas decenas de incidentes.
        $this->medirPeticion('listado con q de frecuencia realista (número de equipo)', $this->tokenAdministrador, '/api/v1/incidentes?q=04821', conMeta: true);
    }

    public function test_historial_de_un_incidente(): void
    {
        $id = DB::table('historial_incidentes')->where('evento', 'modificacion')->orderByDesc('id')->value('incidente_id');

        $this->medirPeticion('historial de un incidente', $this->tokenAdministrador, "/api/v1/incidentes/{$id}/historial", conMeta: true);
    }

    public function test_escenarios_informativos_del_listado(): void
    {
        $total = $this->conToken($this->tokenAdministrador)->getJson('/api/v1/incidentes?per_page=100')->json('meta.total');
        $ultimaPagina = (int) ceil($total / 100);

        $this->medirPeticion('listado, última página con per_page=100 (offset profundo)', $this->tokenAdministrador, "/api/v1/incidentes?per_page=100&page={$ultimaPagina}");
        $this->medirPeticion('listado de quien ve solo lo propio, página 1', $this->tokenSolicitante, '/api/v1/incidentes');
        $this->medirPeticion('listado con q frecuente (una palabra de varias situaciones)', $this->tokenAdministrador, '/api/v1/incidentes?q=impresora');
        $this->medirPeticion('listado con q extrema (palabra que está en todos)', $this->tokenAdministrador, '/api/v1/incidentes?q=equipo');
        $this->assertTrue(true);
    }

    public function test_escenarios_informativos_de_otros_endpoints(): void
    {
        $resuelto = DB::table('incidentes')->where('estado', 'resuelto')->whereNull('deleted_at')->value('id');

        $this->medirPeticion('dashboard de quien ve todo', $this->tokenAdministrador, '/api/v1/dashboard');
        $this->medirPeticion('dashboard de quien ve solo lo propio', $this->tokenSolicitante, '/api/v1/dashboard');
        $this->medirPeticion('incidentes similares', $this->tokenAdministrador, "/api/v1/incidentes/{$resuelto}/similares");
        $this->medirPeticion('aviso de incidente masivo', $this->tokenAdministrador, '/api/v1/avisos');
        $this->medirPeticion('listado por vencer en 24 horas', $this->tokenAdministrador, '/api/v1/incidentes?vence_en_horas=24');
        $this->medirPeticion('listado de los que creó el administrador (mios=1)', $this->tokenAdministrador, '/api/v1/incidentes?mios=1');
        $this->medirPeticion('perfil (GET me)', $this->tokenAdministrador, '/api/v1/me');
        $this->assertTrue(true);
    }
}
