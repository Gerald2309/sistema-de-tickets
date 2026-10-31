<?php

namespace Database\Seeders;

use App\Identidad\Models\Rol;
use App\Incidentes\Dominio\Estado;
use Database\Factories\IncidenteFactory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

// Datos para medir el rendimiento. No forma parte de `migrate:fresh --seed`: se lanza aparte sobre una base
// vacía con `php artisan db:seed --class=VolumenSeeder`, y la cantidad sale de VOLUMEN_INCIDENTES (hasta
// 1.000.000). Todo se genera dentro de MySQL con INSERT ... SELECT, y el reparto se calcula con CRC32 en
// lugar de números al azar, así que dos ejecuciones producen los mismos datos.
class VolumenSeeder extends Seeder
{
    private const SOLICITANTES = 450;

    private const SOPORTE = 50;

    private const EMPRESAS = 20;

    // Los pendientes son recientes; los terminados, de los últimos dos años.
    private const DIAS_DE_ANTIGUEDAD = ['abierto' => 30, 'en_progreso' => 30, 'resuelto' => 730, 'cerrado' => 730];

    // Para que la búsqueda tenga palabras comunes (las de la situación) y raras (el número del equipo).
    private const SEDES = ['Bogotá Norte', 'Bogotá Centro', 'Medellín', 'Cali', 'Barranquilla', 'Bucaramanga', 'Cartagena', 'Pereira', 'Manizales', 'Cúcuta', 'Ibagué', 'Pasto'];

    public function run(): void
    {
        $this->call([PermisosSeeder::class, RolesSeeder::class, AdministradorSeeder::class]);

        $cantidad = min((int) config('incidentes.volumen.incidentes'), 1_000_000);

        $primeraEmpresa = $this->crearEmpresas();
        [$primerSolicitante, $primerSoporte] = $this->crearUsuarios($primeraEmpresa);
        $this->crearIncidentes($cantidad, $primerSolicitante, $primerSoporte);
        // Cada incidente con la empresa de quien lo reportó (F-10).
        DB::statement('UPDATE incidentes i JOIN users u ON u.id = i.usuario_creador_id SET i.cliente_id = u.cliente_id');
        $this->crearHistorial();
    }

    /**
     * @return int id de la primera empresa (los ids son seguidos)
     */
    private function crearEmpresas(): int
    {
        $ahora = now();
        DB::table('clientes')->insert(array_map(fn (int $n) => [
            'nombre' => "Empresa de volumen {$n}",
            'activo' => true,
            'created_at' => $ahora,
            'updated_at' => $ahora,
        ], range(1, self::EMPRESAS)));

        return (int) DB::table('clientes')->where('nombre', 'Empresa de volumen 1')->value('id');
    }

    /**
     * Los solicitantes se reparten entre las empresas; el personal de soporte no tiene.
     *
     * @return array{int, int} id del primer solicitante y del primer usuario de soporte (los ids son seguidos)
     */
    private function crearUsuarios(int $primeraEmpresa): array
    {
        $clave = Hash::make(Str::random(32));
        $ahora = now();
        $roles = Rol::whereIn('nombre', ['Solicitante', 'Soporte N1', 'Soporte N2', 'Soporte N3'])->pluck('id', 'nombre');

        $filas = [];
        for ($i = 0; $i < self::SOLICITANTES + self::SOPORTE; $i++) {
            $esSolicitante = $i < self::SOLICITANTES;
            $filas[] = [
                'name' => fake()->name(),
                'email' => 'volumen'.($i + 1).'@empresa-ejemplo.com',
                'password' => $clave,
                'activo' => true,
                'rol_id' => $esSolicitante ? $roles['Solicitante'] : $roles['Soporte N'.(1 + $i % 3)],
                'cliente_id' => $esSolicitante ? $primeraEmpresa + $i % self::EMPRESAS : null,
                'created_at' => $ahora,
                'updated_at' => $ahora,
            ];
        }
        DB::table('users')->insert($filas);

        $primero = (int) DB::table('users')->where('email', 'volumen1@empresa-ejemplo.com')->value('id');

        return [$primero, $primero + self::SOLICITANTES];
    }

    private function crearIncidentes(int $cantidad, int $primerSolicitante, int $primerSoporte): void
    {
        $this->crearTablaDeSituaciones();

        $estado = $this->segunElEstado(array_combine(array_keys(IncidenteFactory::ESTADOS), array_map(fn (string $e) => "'{$e}'", array_keys(IncidenteFactory::ESTADOS))));
        $dias = $this->segunElEstado(self::DIAS_DE_ANTIGUEDAD);
        $prioridad = "SUBSTRING_INDEX(SUBSTRING_INDEX(s.prioridades, ',', 1 + h.prioridad_azar % s.cuantas), ',', -1)";
        $creado = "(NOW() - INTERVAL (h.antiguedad % ({$dias} * 1440)) MINUTE)";
        $plazo = 'CASE '.$prioridad.collect(IncidenteFactory::PLAZO_HORAS)->map(fn (int $horas, string $p) => " WHEN '{$p}' THEN {$horas}")->implode('').' END';
        $solicitantes = self::SOLICITANTES;
        $soporte = self::SOPORTE;
        $situaciones = count(IncidenteFactory::SITUACIONES);
        $sedes = implode(', ', array_map(fn (string $sede) => "'{$sede}'", self::SEDES));

        DB::statement(<<<SQL
            INSERT INTO incidentes
                (titulo, descripcion, prioridad, estado, categoria, solucion, usuario_creador_id, usuario_asignado_id,
                 fecha_vencimiento, created_at, updated_at, deleted_at)
            SELECT
                s.titulo,
                CONCAT(s.descripcion, ' Equipo PC-', LPAD(h.equipo, 5, '0'), ' en la sede ', ELT(1 + h.sede, {$sedes})),
                {$prioridad},
                {$estado},
                s.categoria,
                IF({$estado} IN ('resuelto', 'cerrado'), s.solucion, NULL),
                {$primerSolicitante} + h.creador,
                IF(h.asignado_azar < 20, NULL, {$primerSoporte} + h.asignado),
                {$creado} + INTERVAL (({$plazo}) * (1 + h.margen)) HOUR,
                {$creado},
                IF({$estado} = 'abierto', {$creado}, LEAST(NOW(), {$creado} + INTERVAL h.atencion MINUTE)),
                IF(h.borrado = 0, {$creado} + INTERVAL 1 MINUTE, NULL)
            FROM (
                SELECT
                    n,
                    1 + CRC32(CONCAT('situacion', n)) % {$situaciones} AS situacion,
                    CRC32(CONCAT('estado', n)) % 100 AS estado_azar,
                    CRC32(CONCAT('prioridad', n)) AS prioridad_azar,
                    CRC32(CONCAT('antiguedad', n)) AS antiguedad,
                    CRC32(CONCAT('margen', n)) % 20 AS margen,
                    CRC32(CONCAT('atencion', n)) % 10080 AS atencion,
                    CRC32(CONCAT('equipo', n)) % 100000 AS equipo,
                    CRC32(CONCAT('sede', n)) % 12 AS sede,
                    CRC32(CONCAT('creador', n)) % {$solicitantes} AS creador,
                    CRC32(CONCAT('asignado', n)) % {$soporte} AS asignado,
                    CRC32(CONCAT('asignado_azar', n)) % 100 AS asignado_azar,
                    CRC32(CONCAT('borrado', n)) % 100 AS borrado
                FROM (SELECT a.d + 10 * b.d + 100 * c.d + 1000 * d.d + 10000 * e.d + 100000 * f.d + 1 AS n
                      FROM {$this->digitos()} a, {$this->digitos()} b, {$this->digitos()} c,
                           {$this->digitos()} d, {$this->digitos()} e, {$this->digitos()} f) numeros
                WHERE n <= {$cantidad}
            ) h
            JOIN volumen_situaciones s ON s.id = h.situacion
            SQL);
    }

    private function crearHistorial(): void
    {
        $estados = Estado::cases();
        $orden = implode(', ', array_map(fn (Estado $e) => "'{$e->value}'", $estados));
        $pasos = collect($estados)->slice(1)->values()->map(
            fn (Estado $hacia, int $i) => 'SELECT '.($i + 1)." AS paso, '{$estados[$i]->value}' AS desde, '{$hacia->value}' AS hacia",
        )->implode(' UNION ALL ');

        // Un incidente que llegó a un estado pasó por todos los anteriores, repartidos entre su creación y su última actualización.
        DB::statement(<<<SQL
            INSERT INTO historial_incidentes (incidente_id, usuario_id, evento, campo, valor_anterior, valor_nuevo, created_at)
            SELECT id, usuario_creador_id, 'creacion', NULL, NULL, NULL, created_at FROM incidentes
            UNION ALL
            SELECT i.id, COALESCE(i.usuario_asignado_id, i.usuario_creador_id), 'modificacion', 'estado', p.desde, p.hacia,
                   TIMESTAMPADD(SECOND, TIMESTAMPDIFF(SECOND, i.created_at, i.updated_at) * p.paso / 3, i.created_at)
            FROM incidentes i
            JOIN ({$pasos}) p ON p.paso < FIELD(i.estado, {$orden})
            UNION ALL
            SELECT id, usuario_creador_id, 'borrado', NULL, NULL, NULL, deleted_at FROM incidentes WHERE deleted_at IS NOT NULL
            SQL);
    }

    private function crearTablaDeSituaciones(): void
    {
        DB::statement('DROP TEMPORARY TABLE IF EXISTS volumen_situaciones');
        DB::statement('CREATE TEMPORARY TABLE volumen_situaciones (
            id INT PRIMARY KEY, titulo VARCHAR(255), descripcion TEXT, categoria VARCHAR(20),
            prioridades VARCHAR(40), cuantas INT, solucion TEXT
        ) DEFAULT CHARSET utf8mb4 COLLATE utf8mb4_0900_ai_ci');

        foreach (IncidenteFactory::SITUACIONES as $i => [$titulo, $descripcion, $categoria, $prioridades, $solucion]) {
            DB::table('volumen_situaciones')->insert([
                'id' => $i + 1, 'titulo' => $titulo, 'descripcion' => $descripcion, 'categoria' => $categoria,
                'prioridades' => implode(',', $prioridades), 'cuantas' => count($prioridades), 'solucion' => $solucion,
            ]);
        }
    }

    /**
     * Un CASE sobre el reparto de estados del factory: cada estado ocupa su porcentaje de 0 a 99.
     *
     * @param  array<string, string|int>  $valores  valor SQL de cada estado
     */
    private function segunElEstado(array $valores): string
    {
        $acumulado = 0;
        $casos = '';
        foreach (IncidenteFactory::ESTADOS as $estado => $datos) {
            $acumulado += $datos['peso'];
            $casos .= " WHEN h.estado_azar < {$acumulado} THEN {$valores[$estado]}";
        }

        return "CASE{$casos} END";
    }

    private function digitos(): string
    {
        return '(SELECT 0 AS d UNION ALL SELECT 1 UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4 UNION ALL SELECT 5 UNION ALL SELECT 6 UNION ALL SELECT 7 UNION ALL SELECT 8 UNION ALL SELECT 9)';
    }
}
