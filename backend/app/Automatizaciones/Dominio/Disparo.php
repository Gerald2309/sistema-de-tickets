<?php

namespace App\Automatizaciones\Dominio;

use App\Automatizaciones\Dominio\Excepciones\FlujoInvalido;

// El paso «Cuándo»: el disparador y la medida que lleva. Solo guarda la que ese disparador usa.
final readonly class Disparo
{
    public const ESPERA_MAXIMA_MINUTOS = 60 * 24 * 90;

    private function __construct(
        public Disparador $disparador,
        public ?int $esperaMinutos = null,
        public ?int $porcentaje = null,
        public ?PlazoDelSla $plazo = null,
    ) {}

    /**
     * @param  array<string, mixed>  $medida  `espera_minutos`, `porcentaje` y `plazo`, según el disparador
     *
     * @throws FlujoInvalido
     */
    public static function desde(mixed $disparador, array $medida): self
    {
        $elegido = Disparador::tryFrom(is_string($disparador) ? $disparador : '')
            ?? throw new FlujoInvalido('disparador', 'Ese disparador no existe.');

        return match ($elegido) {
            Disparador::Creacion => new self($elegido),
            Disparador::SinRespuesta, Disparador::ResueltoSinConfirmar => new self($elegido, esperaMinutos: self::espera($medida)),
            Disparador::SlaVencido => new self($elegido, plazo: self::plazo($medida)),
            Disparador::SlaPorVencer => new self($elegido, porcentaje: self::porcentaje($medida), plazo: self::plazo($medida)),
        };
    }

    /**
     * @return array{disparador: string, espera_minutos: int|null, porcentaje: int|null, plazo: string|null}
     */
    public function aArreglo(): array
    {
        return [
            'disparador' => $this->disparador->value,
            'espera_minutos' => $this->esperaMinutos,
            'porcentaje' => $this->porcentaje,
            'plazo' => $this->plazo?->value,
        ];
    }

    /**
     * @param  array<string, mixed>  $medida
     */
    private static function espera(array $medida): int
    {
        $minutos = $medida['espera_minutos'] ?? null;

        return is_int($minutos) && $minutos >= 1 && $minutos <= self::ESPERA_MAXIMA_MINUTOS
            ? $minutos
            : throw new FlujoInvalido('espera_minutos', 'La espera debe ser de 1 minuto a 90 días.');
    }

    /**
     * @param  array<string, mixed>  $medida
     */
    private static function porcentaje(array $medida): int
    {
        $porcentaje = $medida['porcentaje'] ?? null;

        return is_int($porcentaje) && $porcentaje >= 1 && $porcentaje <= 99
            ? $porcentaje
            : throw new FlujoInvalido('porcentaje', 'Debe ser un porcentaje del plazo entre 1 y 99.');
    }

    /**
     * @param  array<string, mixed>  $medida
     */
    private static function plazo(array $medida): PlazoDelSla
    {
        return PlazoDelSla::tryFrom(is_string($medida['plazo'] ?? null) ? $medida['plazo'] : '')
            ?? throw new FlujoInvalido('plazo', 'Elija el plazo de respuesta o el de resolución.');
    }
}
