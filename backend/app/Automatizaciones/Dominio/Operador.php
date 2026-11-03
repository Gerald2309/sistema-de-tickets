<?php

namespace App\Automatizaciones\Dominio;

// Cómo compara una condición: con una lista de opciones o, si el campo tiene orden, con un solo valor.
enum Operador: string
{
    case EsUnaDe = 'es_una_de';
    case NoEsNingunaDe = 'no_es_ninguna_de';
    case AlMenos = 'al_menos';
    case ComoMaximo = 'como_maximo';
    case Igual = 'igual';

    // ≥, ≤ o =: comparan con un valor en orden, no con una lista.
    public function comparaConUnValor(): bool
    {
        return ! in_array($this, [self::EsUnaDe, self::NoEsNingunaDe], true);
    }

    public function cumple(int $medida, int $limite): bool
    {
        return match ($this) {
            self::AlMenos => $medida >= $limite,
            self::ComoMaximo => $medida <= $limite,
            default => $medida === $limite,
        };
    }
}
