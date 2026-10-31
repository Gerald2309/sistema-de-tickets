<?php

namespace Database\Seeders;

use App\Dominio\AccionAuditoria;
use App\Dominio\CampoHistorial;
use App\Dominio\Estado;
use App\Dominio\EventoHistorial;
use App\Models\Auditoria;
use App\Models\HistorialIncidente;
use App\Models\Incidente;
use App\Models\Rol;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;

// Datos de demostración: usuarios con nombres reales y una mesa de soporte con incidentes en
// todos los estados, con su historial y la auditoría de las altas. Las cuentas llevan
// contraseñas aleatorias.
class DatosDemoSeeder extends Seeder
{
    private const INCIDENTES = 40;

    private const IP_ADMINISTRADOR = '192.168.10.5';

    public function run(): void
    {
        $administrador = User::where('email', config('seguridad.administrador.email'))->firstOrFail();

        $solicitantes = $this->usuarios('Solicitante', 6, $administrador);
        $soporte = $this->usuarios('Soporte N1', 2, $administrador)
            ->concat($this->usuarios('Soporte N2', 2, $administrador))
            ->concat($this->usuarios('Soporte N3', 1, $administrador));

        $this->registrarInicioDeSesionFallido($solicitantes);

        for ($i = 0; $i < self::INCIDENTES; $i++) {
            $incidente = Incidente::factory()
                ->state(fn (array $atributos) => [
                    'usuario_creador_id' => $solicitantes->random()->id,
                    // Un incidente sin atender (abierto) puede no tener todavía asignado.
                    'usuario_asignado_id' => $atributos['estado'] === Estado::Abierto && fake()->boolean(50)
                        ? null
                        : $soporte->random()->id,
                ])
                ->create();

            $this->registrarHistorial($incidente);
        }
    }

    /**
     * Crea usuarios con el rol indicado y deja constancia de su alta en la auditoría.
     *
     * @return Collection<int, User>
     */
    private function usuarios(string $rol, int $cantidad, User $administrador): Collection
    {
        $usuarios = User::factory($cantidad)->create(['rol_id' => Rol::where('nombre', $rol)->firstOrFail()->id]);

        Auditoria::insert($usuarios->map(fn (User $usuario) => [
            'usuario_id' => $administrador->id,
            'accion' => AccionAuditoria::UsuarioCreado->value,
            'usuario_afectado_id' => $usuario->id,
            'detalle' => "Rol «{$rol}»",
            'ip' => self::IP_ADMINISTRADOR,
            'created_at' => now(),
        ])->all());

        return $usuarios;
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
