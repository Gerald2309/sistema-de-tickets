<?php

namespace Database\Seeders;

use App\Incidentes\Dominio\Estado;
use Carbon\CarbonImmutable;

// Cómo transcurre un incidente de demostración: se atiende y se resuelve en proporción a sus plazos, casi siempre
// a tiempo, y unos pocos se atascan o se devuelven. El estado sale de los pasos que ya ocurrieron: lo reciente
// sigue pendiente y lo antiguo ya se cerró, como en una mesa de ayuda real.
final readonly class VidaDeUnIncidente
{
    private const PROBABILIDAD_A_TIEMPO = 82;

    private const PROBABILIDAD_DE_ATASCARSE = 2;

    private const PROBABILIDAD_DE_DEVOLVERSE = 5;

    // Quien no confirma la solución deja el incidente en «resuelto».
    private const PROBABILIDAD_DE_CONFIRMAR = 85;

    /**
     * @param  list<array{momento: CarbonImmutable, de: Estado, a: Estado}>  $pasos  los que ya ocurrieron, en orden
     */
    private function __construct(
        public CarbonImmutable $creado,
        public array $pasos,
    ) {}

    /**
     * @param  array{respuesta: CarbonImmutable, resolucion: CarbonImmutable}  $limites
     */
    public static function simular(CarbonImmutable $creado, array $limites, CarbonImmutable $ahora): self
    {
        $pasos = [];
        $atencion = self::instante($creado, $limites['respuesta']);
        $pasos[] = ['momento' => $atencion, 'de' => Estado::Abierto, 'a' => Estado::EnProgreso];

        if (random_int(1, 100) > self::PROBABILIDAD_DE_ATASCARSE) {
            // Nunca antes de atenderlo, aunque el plazo de resolución sea corto.
            $resuelto = self::instante($creado, $limites['resolucion'])->max($atencion->addMinutes(random_int(5, 30)));
            $pasos[] = ['momento' => $resuelto, 'de' => Estado::EnProgreso, 'a' => Estado::Resuelto];

            if (random_int(1, 100) <= self::PROBABILIDAD_DE_DEVOLVERSE) {
                $devuelto = $resuelto->addHours(random_int(1, 24));
                $resuelto = $devuelto->addHours(random_int(1, 24));
                $pasos[] = ['momento' => $devuelto, 'de' => Estado::Resuelto, 'a' => Estado::EnProgreso];
                $pasos[] = ['momento' => $resuelto, 'de' => Estado::EnProgreso, 'a' => Estado::Resuelto];
            }

            if (random_int(1, 100) <= self::PROBABILIDAD_DE_CONFIRMAR) {
                $pasos[] = ['momento' => $resuelto->addHours(random_int(2, 96)), 'de' => Estado::Resuelto, 'a' => Estado::Cerrado];
            }
        }

        return new self($creado, array_values(array_filter($pasos, fn (array $paso) => $paso['momento'] < $ahora)));
    }

    public function estado(): Estado
    {
        return $this->pasos === [] ? Estado::Abierto : $this->pasos[array_key_last($this->pasos)]['a'];
    }

    public function actualizado(): CarbonImmutable
    {
        return $this->pasos === [] ? $this->creado : $this->pasos[array_key_last($this->pasos)]['momento'];
    }

    public function primeraRespuesta(): ?CarbonImmutable
    {
        return $this->pasos[0]['momento'] ?? null;
    }

    // La última vez que se resolvió, si sigue resuelto o se cerró.
    public function resuelto(): ?CarbonImmutable
    {
        if (! in_array($this->estado(), [Estado::Resuelto, Estado::Cerrado], true)) {
            return null;
        }

        foreach (array_reverse($this->pasos) as $paso) {
            if ($paso['a'] === Estado::Resuelto) {
                return $paso['momento'];
            }
        }

        return null;
    }

    // Dentro del plazo la mayoría de las veces; el resto se pasa, sin exagerar.
    private static function instante(CarbonImmutable $creado, CarbonImmutable $limite): CarbonImmutable
    {
        $plazo = $creado->diffInSeconds($limite, true);
        $porcentaje = random_int(1, 100) <= self::PROBABILIDAD_A_TIEMPO ? random_int(25, 95) : random_int(105, 200);

        return $creado->addSeconds((int) ($plazo * $porcentaje / 100));
    }
}
