<?php

namespace Tests\Rendimiento;

use App\Identidad\Models\Rol;
use App\Identidad\Models\User;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use PHPUnit\Framework\Attributes\DataProvider;

// Los índices se verifican con el plan de ejecución, no por intuición: cada consulta que el listado lanza de
// verdad (la página y su conteo) se pasa por EXPLAIN sobre la base de volumen y no puede recorrer la tabla
// de incidentes entera.
class PlanesDeConsultaTest extends PruebaDeRendimiento
{
    /**
     * @return array<string, array{string, bool}>
     */
    public static function listados(): array
    {
        return [
            'sin filtros' => ['', false],
            'por estado' => ['estado=abierto', false],
            'por prioridad, ordenado por vencimiento' => ['prioridad=critica&ordenar_por=fecha_vencimiento&direccion=asc', false],
            'vencidos' => ['vencidos=1', false],
            'por vencer en 24 horas' => ['vence_en_horas=24', false],
            'búsqueda de texto' => ['q=impresora', false],
            'por empresa' => ['cliente_id=3', false],
            'de quien ve solo lo propio' => ['', true],
        ];
    }

    #[DataProvider('listados')]
    public function test_el_listado_no_recorre_la_tabla_entera(string $consulta, bool $comoSolicitante): void
    {
        $token = $comoSolicitante ? $this->tokenDeSolicitante() : $this->tokenAdministrador;
        $lanzadas = [];
        Event::listen(QueryExecuted::class, function (QueryExecuted $ejecutada) use (&$lanzadas): void {
            if (str_starts_with($ejecutada->sql, 'select') && str_contains($ejecutada->sql, 'from `incidentes`')) {
                $lanzadas[] = $ejecutada;
            }
        });

        $this->conToken($token)->getJson('/api/v1/incidentes?'.$consulta)->assertOk();

        $this->assertNotEmpty($lanzadas);
        foreach ($lanzadas as $ejecutada) {
            foreach (DB::select('EXPLAIN '.$ejecutada->sql, $ejecutada->bindings) as $paso) {
                if ($paso->table !== 'incidentes') {
                    continue;
                }
                fwrite(STDOUT, sprintf("  %-42s type=%-9s key=%-48s rows=%s\n", $consulta ?: '(sin filtros)', $paso->type, $paso->key ?? '-', $paso->rows));
                $this->assertNotSame('ALL', $paso->type, "Recorre la tabla entera: {$ejecutada->sql}");
            }
        }
    }

    private function tokenDeSolicitante(): string
    {
        return User::where('rol_id', Rol::where('nombre', 'Solicitante')->value('id'))->orderBy('id')->firstOrFail()
            ->createToken('rendimiento')->plainTextToken;
    }
}
