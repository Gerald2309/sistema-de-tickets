<?php

namespace App\Automatizaciones\Dominio;

use App\Incidentes\Dominio\Categoria;
use App\Incidentes\Dominio\Prioridad;

// Lo que una condición puede preguntar de un incidente, qué valores admite y cómo se compara.
enum CampoDeCondicion: string
{
    case Empresa = 'empresa';
    case Categoria = 'categoria';
    case Prioridad = 'prioridad';
    case Palabras = 'palabras';
    case PlazoDeRespuestaConsumido = 'plazo_respuesta_consumido';
    case PlazoDeResolucionConsumido = 'plazo_resolucion_consumido';

    public const LONGITUD_MAXIMA_DE_PALABRAS = 60;

    // Un plazo consumido pasa de 100 cuando se vence; más allá de esto ya no distingue nada.
    public const PORCENTAJE_MAXIMO = 1000;

    /**
     * Las comparaciones que tienen sentido: con orden (prioridad, plazos) se puede comparar con un valor.
     *
     * @return non-empty-list<Operador>
     */
    public function operadores(): array
    {
        return match ($this) {
            self::Empresa, self::Categoria, self::Palabras => [Operador::EsUnaDe, Operador::NoEsNingunaDe],
            self::Prioridad => [Operador::EsUnaDe, Operador::NoEsNingunaDe, Operador::AlMenos, Operador::ComoMaximo],
            self::PlazoDeRespuestaConsumido, self::PlazoDeResolucionConsumido => [Operador::AlMenos, Operador::ComoMaximo, Operador::Igual],
        };
    }

    public function admite(mixed $valor): bool
    {
        return match ($this) {
            self::Empresa => is_int($valor) && $valor > 0,
            self::Categoria => is_string($valor) && Categoria::tryFrom($valor) !== null,
            self::Prioridad => is_string($valor) && Prioridad::tryFrom($valor) !== null,
            self::Palabras => is_string($valor) && trim($valor) !== '' && mb_strlen($valor) <= self::LONGITUD_MAXIMA_DE_PALABRAS,
            self::PlazoDeRespuestaConsumido, self::PlazoDeResolucionConsumido => is_int($valor) && $valor >= 0 && $valor <= self::PORCENTAJE_MAXIMO,
        };
    }

    /**
     * Si el incidente tiene alguno de esos valores (para «es una de» y «no es ninguna de»).
     *
     * @param  non-empty-list<int|string>  $valores
     */
    public function coincide(Hechos $hechos, array $valores): bool
    {
        return match ($this) {
            self::Empresa => in_array($hechos->empresaId, $valores, true),
            self::Categoria => in_array($hechos->categoria->value, $valores, true),
            self::Prioridad => in_array($hechos->prioridad->value, $valores, true),
            self::Palabras => array_any($valores, fn (int|string $palabras) => $hechos->menciona((string) $palabras)),
            default => false,
        };
    }

    /**
     * El valor del incidente en la escala del campo, para comparar en orden; null si no lo tiene (un incidente
     * sin ese plazo).
     */
    public function medidaDe(Hechos $hechos): ?int
    {
        return match ($this) {
            self::Prioridad => self::nivel($hechos->prioridad),
            self::PlazoDeRespuestaConsumido => $hechos->consumoDeRespuesta,
            self::PlazoDeResolucionConsumido => $hechos->consumoDeResolucion,
            default => null,
        };
    }

    // El valor elegido en la misma escala que medidaDe.
    public function medidaDelValor(int|string $valor): int
    {
        return $this === self::Prioridad ? self::nivel(Prioridad::from((string) $valor)) : (int) $valor;
    }

    // Prioridad está declarada de la menor a la mayor: su posición es su nivel.
    private static function nivel(Prioridad $prioridad): int
    {
        return (int) array_search($prioridad, Prioridad::cases(), true);
    }
}
