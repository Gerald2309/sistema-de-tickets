<?php

namespace App\Automatizaciones\Dominio;

use App\Incidentes\Dominio\Categoria;
use App\Incidentes\Dominio\Prioridad;

// Lo que una condición puede preguntar de un incidente y qué valores admite cada pregunta.
enum CampoDeCondicion: string
{
    case Empresa = 'empresa';
    case Categoria = 'categoria';
    case Prioridad = 'prioridad';
    case Palabras = 'palabras';

    public const LONGITUD_MAXIMA_DE_PALABRAS = 60;

    public function admite(mixed $valor): bool
    {
        return match ($this) {
            self::Empresa => is_int($valor) && $valor > 0,
            self::Categoria => is_string($valor) && Categoria::tryFrom($valor) !== null,
            self::Prioridad => is_string($valor) && Prioridad::tryFrom($valor) !== null,
            self::Palabras => is_string($valor) && trim($valor) !== '' && mb_strlen($valor) <= self::LONGITUD_MAXIMA_DE_PALABRAS,
        };
    }

    /**
     * Si el incidente tiene alguno de esos valores.
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
        };
    }
}
