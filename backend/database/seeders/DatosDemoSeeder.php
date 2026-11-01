<?php

namespace Database\Seeders;

use App\Auditoria\Dominio\AccionAuditoria;
use App\Auditoria\Models\Auditoria;
use App\Identidad\Models\Cliente;
use App\Identidad\Models\Rol;
use App\Identidad\Models\User;
use App\Incidentes\Consultas\PlazosDeLaEmpresa;
use App\Incidentes\Dominio\CampoHistorial;
use App\Incidentes\Dominio\Categoria;
use App\Incidentes\Dominio\Estado;
use App\Incidentes\Dominio\EventoHistorial;
use App\Incidentes\Dominio\PlazosDeServicio;
use App\Incidentes\Dominio\Prioridad;
use App\Incidentes\Models\AcuerdoDeServicio;
use App\Incidentes\Models\HistorialIncidente;
use App\Incidentes\Models\Incidente;
use Carbon\CarbonImmutable;
use Database\Factories\IncidenteFactory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;

// Datos de demostración: empresas cliente, usuarios con nombres reales y una mesa de soporte con
// incidentes que siguen los plazos de su empresa (VidaDeUnIncidente), con su historial y la auditoría de las altas. Las cuentas llevan
// contraseñas aleatorias, salvo una de prueba por perfil (CUENTAS_DE_PRUEBA), con DEMO_PASSWORD. Los solicitantes se reparten entre las empresas; el personal de soporte no tiene.

class DatosDemoSeeder extends Seeder
{
    private const INCIDENTES = 750;

    // Días hacia atrás que abarca la demostración, y la parte de los incidentes que entró en la última semana.
    private const DIAS_DE_HISTORIA = 60;

    private const PROBABILIDAD_ESTA_SEMANA = 35;

    // Fuera de la jornada también entran incidentes, pero pocos.
    private const PROBABILIDAD_FUERA_DE_HORARIO = 10;

    private const IP_ADMINISTRADOR = '192.168.10.5';

    // Una cuenta conocida por perfil (la primera de cada rol), para entrar con cada uno. Personas inventadas.
    private const CUENTAS_DE_PRUEBA = [
        'Soporte N1' => ['Cristina Guillen', 'cristina.guillen@empresa-ejemplo.com'],
        'Soporte N2' => ['Diego Betancourt', 'diego.betancourt@empresa-ejemplo.com'],
        'Soporte N3' => ['Valentina Ojeda', 'valentina.ojeda@empresa-ejemplo.com'],
        'Solicitante' => ['Úrsula Apodaca', 'ursula.apodaca@empresa-ejemplo.com'],
    ];

    // Nombres inventados para la demostración.
    private const EMPRESAS = ['Constructora Andina', 'Transportes Sabana', 'Clínica del Norte', 'Banco Aliado', 'Colegio Los Pinos'];

    public function run(): void
    {
        $administrador = User::where('email', config('seguridad.administrador.email'))->firstOrFail();

        $empresas = $this->empresas($administrador);
        $this->acuerdoDeServicio($empresas->firstOrFail());
        $solicitantes = $this->usuarios('Solicitante', 12, $administrador, $empresas);
        $soporte = $this->usuarios('Soporte N1', 2, $administrador)
            ->concat($this->usuarios('Soporte N2', 2, $administrador))
            ->concat($this->usuarios('Soporte N3', 1, $administrador));

        $this->registrarInicioDeSesionFallido($solicitantes);

        for ($i = 0; $i < self::INCIDENTES; $i++) {
            $this->incidente($solicitantes->random(), $soporte);
        }

        $this->reportesDeUnaCaidaDeRed($solicitantes);
    }

    /**
     * @param  Collection<int, User>  $soporte
     */
    private function incidente(User $creador, Collection $soporte): void
    {
        [$titulo, $descripcion, $categoria, $prioridades, $solucion] = fake()->randomElement(IncidenteFactory::SITUACIONES);
        $prioridad = Prioridad::from(fake()->randomElement($prioridades));
        $creado = $this->momentoDeCreacion();
        $limites = app(PlazosDeLaEmpresa::class)->limites($creador->cliente_id, $prioridad, $creado);
        $vida = VidaDeUnIncidente::simular($creado, $limites, now()->toImmutable());

        $incidente = Incidente::factory()->create([
            'titulo' => $titulo,
            'descripcion' => $descripcion,
            'categoria' => Categoria::from($categoria),
            'prioridad' => $prioridad,
            'estado' => $vida->estado(),
            'solucion' => $vida->resuelto() === null ? null : $solucion,
            'usuario_creador_id' => $creador->id,
            'cliente_id' => $creador->cliente_id,
            // Un incidente sin atender (abierto) puede no tener todavía asignado.
            'usuario_asignado_id' => $vida->estado() === Estado::Abierto && fake()->boolean(50) ? null : $soporte->random()->id,
            'fecha_vencimiento' => $limites['resolucion'],
            'vence_respuesta_en' => $limites['respuesta'],
            'primera_respuesta_en' => $vida->primeraRespuesta(),
            'resuelto_en' => $vida->resuelto(),
            'created_at' => $creado,
            'updated_at' => $vida->actualizado(),
        ]);

        $this->registrarHistorial($incidente, $vida->pasos);
    }

    // Casi siempre entre semana y en horario de oficina, en la zona horaria de la operación; nunca en el futuro.
    private function momentoDeCreacion(): CarbonImmutable
    {
        $ahora = now()->toImmutable()->setTimezone((string) config('incidentes.acuerdos.zona_horaria'));
        $dias = fake()->boolean(self::PROBABILIDAD_ESTA_SEMANA) ? random_int(0, 6) : random_int(7, self::DIAS_DE_HISTORIA);
        $momento = $ahora->subDays($dias)->setTime(random_int(8, 17), random_int(0, 59));

        if (! fake()->boolean(self::PROBABILIDAD_FUERA_DE_HORARIO)) {
            while ($momento->isWeekend()) {
                $momento = $momento->subDay();
            }
        } else {
            $momento = $momento->setTime(random_int(0, 23), random_int(0, 59));
        }

        return ($momento < $ahora ? $momento : $ahora->subMinutes(random_int(5, 120)))->utc();
    }

    // Una empresa con plazos más exigentes y en horario laboral, para ver la diferencia con los predeterminados.
    private function acuerdoDeServicio(Cliente $empresa): void
    {
        AcuerdoDeServicio::create([
            'cliente_id' => $empresa->id,
            'horario_laboral' => true,
            'plazos' => array_map(fn (array $plazo) => [
                'respuesta_minutos' => intdiv($plazo['respuesta_minutos'], 2),
                'resolucion_minutos' => intdiv($plazo['resolucion_minutos'], 2),
            ], PlazosDeServicio::PREDETERMINADOS),
        ]);
    }

    /**
     * Cinco reportes recientes de la misma falla, de cuatro personas distintas: dispara el aviso de
     * posible incidente masivo.
     *
     * @param  Collection<int, User>  $solicitantes
     */
    private function reportesDeUnaCaidaDeRed(Collection $solicitantes): void
    {
        $reportes = [
            ['Sin internet en el piso 3', 'Desde hace unos minutos no hay conexión de internet en el piso 3 y nadie puede trabajar.'],
            ['No carga ninguna página web', 'La conexión de internet de mi equipo no responde, ninguna página web abre.'],
            ['Se cayó el internet en contabilidad', 'En contabilidad no hay conexión de internet desde hace media hora.'],
            ['Internet muy lento y se corta', 'La conexión de internet se corta cada pocos minutos y no puedo enviar archivos.'],
            ['No hay conexión en la sala de juntas', 'La sala de juntas perdió la conexión de internet y tenemos una reunión.'],
        ];

        foreach ($reportes as $i => [$titulo, $descripcion]) {
            $momento = now()->toImmutable()->subMinutes(10 + $i * 15);
            /** @var User $creador hay más de cuatro solicitantes */
            $creador = $solicitantes[$i % 4];
            $limites = app(PlazosDeLaEmpresa::class)->limites($creador->cliente_id, Prioridad::Alta, $momento);

            $incidente = Incidente::factory()->create([
                'titulo' => $titulo,
                'descripcion' => $descripcion,
                'categoria' => Categoria::Red,
                'estado' => Estado::Abierto,
                'prioridad' => Prioridad::Alta,
                'solucion' => null,
                'usuario_creador_id' => $creador->id,
                'cliente_id' => $creador->cliente_id,
                'usuario_asignado_id' => null,
                'fecha_vencimiento' => $limites['resolucion'],
                'vence_respuesta_en' => $limites['respuesta'],
                'primera_respuesta_en' => null,
                'resuelto_en' => null,
                'created_at' => $momento,
                'updated_at' => $momento,
            ]);

            $this->registrarHistorial($incidente);
        }
    }

    /**
     * Crea las empresas cliente y deja constancia de su alta en la auditoría.
     *
     * @return Collection<int, Cliente>
     */
    private function empresas(User $administrador): Collection
    {
        $empresas = collect(self::EMPRESAS)->map(fn (string $nombre) => Cliente::create(['nombre' => $nombre]));

        Auditoria::insert($empresas->map(fn (Cliente $empresa) => [
            'usuario_id' => $administrador->id,
            'accion' => AccionAuditoria::ClienteCreado->value,
            'usuario_afectado_id' => null,
            'detalle' => "Empresa «{$empresa->nombre}»",
            'ip' => self::IP_ADMINISTRADOR,
            'created_at' => now(),
        ])->all());

        return $empresas;
    }

    /**
     * Crea usuarios con el rol indicado, repartidos entre las empresas si se dan, y deja constancia de su
     * alta en la auditoría.
     *
     * @param  Collection<int, Cliente>|null  $empresas
     * @return Collection<int, User>
     */
    private function usuarios(string $rol, int $cantidad, User $administrador, ?Collection $empresas = null): Collection
    {
        $usuarios = User::factory($cantidad)
            ->sequence(fn ($secuencia) => ['cliente_id' => $empresas?->get($secuencia->index % $empresas->count())?->id]
                + ($secuencia->index === 0 ? $this->cuentaDePrueba($rol) : []))
            ->create(['rol_id' => Rol::where('nombre', $rol)->firstOrFail()->id])
            ->load('cliente');

        Auditoria::insert($usuarios->map(fn (User $usuario) => [
            'usuario_id' => $administrador->id,
            'accion' => AccionAuditoria::UsuarioCreado->value,
            'usuario_afectado_id' => $usuario->id,
            'detalle' => "Rol «{$rol}»".($usuario->cliente ? " · Empresa «{$usuario->cliente->nombre}»" : ''),
            'ip' => self::IP_ADMINISTRADOR,
            'created_at' => now(),
        ])->all());

        return $usuarios;
    }

    /**
     * @return array<string, string>
     */
    private function cuentaDePrueba(string $rol): array
    {
        $password = config('seguridad.demostracion.password');
        if (! isset(self::CUENTAS_DE_PRUEBA[$rol]) || ! is_string($password) || $password === '') {
            return [];
        }
        [$nombre, $email] = self::CUENTAS_DE_PRUEBA[$rol];

        return ['name' => $nombre, 'email' => $email, 'password' => $password];
    }

    /**
     * @param  Collection<int, User>  $usuarios
     */
    private function registrarInicioDeSesionFallido(Collection $usuarios): void
    {
        for ($i = 0; $i < 3; $i++) {
            Auditoria::insert([[
                'usuario_id' => null,
                'accion' => AccionAuditoria::LoginFallido->value,
                'usuario_afectado_id' => $usuarios->random()->id,
                'detalle' => null,
                'ip' => '192.168.20.'.random_int(10, 200),
                'created_at' => now()->subMinutes(random_int(30, 60 * 48)),
            ]]);
        }
    }

    /**
     * La creación y cada paso de estado. Soporte resuelve; quien lo reportó confirma o devuelve la solución.
     *
     * @param  list<array{momento: CarbonImmutable, de: Estado, a: Estado}>  $pasos
     */
    private function registrarHistorial(Incidente $incidente, array $pasos = []): void
    {
        $filas = [[
            'incidente_id' => $incidente->id,
            'usuario_id' => $incidente->usuario_creador_id,
            'evento' => EventoHistorial::Creacion->value,
            'campo' => null,
            'valor_anterior' => null,
            'valor_nuevo' => null,
            'created_at' => $incidente->created_at,
        ]];

        foreach ($pasos as $paso) {
            $filas[] = [
                'incidente_id' => $incidente->id,
                'usuario_id' => $paso['de'] === Estado::Resuelto ? $incidente->usuario_creador_id : $incidente->usuario_asignado_id,
                'evento' => EventoHistorial::Modificacion->value,
                'campo' => CampoHistorial::Estado->value,
                'valor_anterior' => $paso['de']->value,
                'valor_nuevo' => $paso['a']->value,
                'created_at' => $paso['momento'],
            ];
        }

        HistorialIncidente::insert($filas);
    }
}
