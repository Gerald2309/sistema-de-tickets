<?php

namespace App\Incidentes\Dominio;

use Carbon\CarbonImmutable;

// Lo que una empresa tiene acordado: minutos de primera respuesta y de resolución por prioridad, y si corren en
// horario laboral o de forma continua. Quien no tiene acuerdo propio usa los predeterminados.
final readonly class PlazosDeServicio
{
    public const PREDETERMINADOS = [
        'critica' => ['respuesta_minutos' => 15, 'resolucion_minutos' => 120],
        'alta' => ['respuesta_minutos' => 60, 'resolucion_minutos' => 480],
        'media' => ['respuesta_minutos' => 240, 'resolucion_minutos' => 1440],
        'baja' => ['respuesta_minutos' => 480, 'resolucion_minutos' => 4320],
    ];

    /**
     * @param  array<string, array{respuesta_minutos: int, resolucion_minutos: int}>  $plazos  una entrada por prioridad
     */
    public function __construct(
        public bool $horarioLaboral,
        public array $plazos,
    ) {}

    public static function predeterminados(): self
    {
        return new self(false, self::PREDETERMINADOS);
    }

    /**
     * @return array{respuesta: CarbonImmutable, resolucion: CarbonImmutable}
     */
    public function limitesDesde(CarbonImmutable $inicio, Prioridad $prioridad): array
    {
        $plazo = $this->plazos[$prioridad->value];

        return [
            'respuesta' => $this->sumar($inicio, $plazo['respuesta_minutos']),
            'resolucion' => $this->sumar($inicio, $plazo['resolucion_minutos']),
        ];
    }

    private function sumar(CarbonImmutable $inicio, int $minutos): CarbonImmutable
    {
        return $this->horarioLaboral ? HorarioLaboral::sumar($inicio, $minutos) : $inicio->addMinutes($minutos);
    }
}
