<?php

namespace Database\Seeders;

use App\Auditoria\Dominio\AccionAuditoria;
use App\Auditoria\Models\Auditoria;
use App\Identidad\Models\Cliente;
use App\Identidad\Models\Rol;
use App\Identidad\Models\User;
use App\Incidentes\Dominio\CampoHistorial;
use App\Incidentes\Dominio\Categoria;
use App\Incidentes\Dominio\Estado;
use App\Incidentes\Dominio\EventoHistorial;
use App\Incidentes\Dominio\Prioridad;
use App\Incidentes\Models\HistorialIncidente;
use App\Incidentes\Models\Incidente;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;

// Datos de demostración: empresas cliente, usuarios con nombres reales y una mesa de soporte con
// incidentes en todos los estados, con su historial y la auditoría de las altas. Las cuentas llevan
// contraseñas aleatorias, salvo una de prueba por perfil (CUENTAS_DE_PRUEBA), con DEMO_PASSWORD. Los solicitantes se reparten entre las empresas; el personal de soporte no tiene.

class DatosDemoSeeder extends Seeder
{
    private const INCIDENTES = 750;

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
        $solicitantes = $this->usuarios('Solicitante', 12, $administrador, $empresas);
        $soporte = $this->usuarios('Soporte N1', 2, $administrador)
            ->concat($this->usuarios('Soporte N2', 2, $administrador))
            ->concat($this->usuarios('Soporte N3', 1, $administrador));

        $this->registrarInicioDeSesionFallido($solicitantes);

        for ($i = 0; $i < self::INCIDENTES; $i++) {
            $creador = $solicitantes->random();
            $incidente = Incidente::factory()
                ->state(fn (array $atributos) => [
                    'usuario_creador_id' => $creador->id,
                    'cliente_id' => $creador->cliente_id,
                    // Un incidente sin atender (abierto) puede no tener todavía asignado.
                    'usuario_asignado_id' => $atributos['estado'] === Estado::Abierto && fake()->boolean(50)
                        ? null
                        : $soporte->random()->id,
                ])
                ->create();

            $this->registrarHistorial($incidente);
        }

        $this->reportesDeUnaCaidaDeRed($solicitantes);
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
            $momento = now()->subMinutes(10 + $i * 15);
            /** @var User $creador hay más de cuatro solicitantes */
            $creador = $solicitantes[$i % 4];

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

    // Creación y cada paso de estado hasta el actual, repartidos entre la creación y la última actualización.
    private function registrarHistorial(Incidente $incidente): void
    {
        assert($incidente->created_at !== null);

        $filas = [[
            'incidente_id' => $incidente->id,
            'usuario_id' => $incidente->usuario_creador_id,
            'evento' => EventoHistorial::Creacion->value,
            'campo' => null,
            'valor_anterior' => null,
            'valor_nuevo' => null,
            'created_at' => $incidente->created_at,
        ]];

        $ciclo = Estado::cases();
        $pasos = array_search($incidente->estado, $ciclo, true);

        for ($paso = 1; $paso <= $pasos; $paso++) {
            $filas[] = [
                'incidente_id' => $incidente->id,
                'usuario_id' => $incidente->usuario_asignado_id ?? $incidente->usuario_creador_id,
                'evento' => EventoHistorial::Modificacion->value,
                'campo' => CampoHistorial::Estado->value,
                'valor_anterior' => $ciclo[$paso - 1]->value,
                'valor_nuevo' => $ciclo[$paso]->value,
                'created_at' => $incidente->created_at->copy()->addSeconds(
                    (int) ($incidente->created_at->diffInSeconds($incidente->updated_at, true) * $paso / $pasos),
                ),
            ];
        }

        HistorialIncidente::insert($filas);
    }
}
