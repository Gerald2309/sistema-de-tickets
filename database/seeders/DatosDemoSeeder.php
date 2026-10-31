<?php

namespace Database\Seeders;

use App\Dominio\CampoHistorial;
use App\Dominio\Estado;
use App\Dominio\EventoHistorial;
use App\Models\HistorialIncidente;
use App\Models\Incidente;
use App\Models\Rol;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;

// Datos de demostración: usuarios con nombres reales y una mesa de soporte con incidentes en
// todos los estados, con su historial. Las cuentas llevan contraseñas aleatorias.
class DatosDemoSeeder extends Seeder
{
    private const INCIDENTES = 40;

    public function run(): void
    {
        $solicitantes = $this->usuarios('Solicitante', 6);
        $soporte = $this->usuarios('Soporte N1', 2)
            ->concat($this->usuarios('Soporte N2', 2))
            ->concat($this->usuarios('Soporte N3', 1));

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
     * @return Collection<int, User>
     */
    private function usuarios(string $rol, int $cantidad): Collection
    {
        return User::factory($cantidad)->create(['rol_id' => Rol::where('nombre', $rol)->firstOrFail()->id]);
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
