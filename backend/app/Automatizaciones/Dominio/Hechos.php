<?php

namespace App\Automatizaciones\Dominio;

use App\Incidentes\Dominio\Categoria;
use App\Incidentes\Dominio\Prioridad;

// Lo que las condiciones pueden preguntar de un incidente, tomado en el momento de evaluarlo.
final readonly class Hechos
{
    private const SIN_TILDES = ['á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ü' => 'u'];

    private string $texto;

    /**
     * @param  string  $texto  título y descripción
     * @param  int|null  $consumoDeRespuesta  porcentaje del plazo de respuesta que ya pasó (ConsumoDelPlazo)
     * @param  int|null  $consumoDeResolucion  porcentaje del plazo de resolución que ya pasó
     */
    public function __construct(
        public ?int $empresaId,
        public Categoria $categoria,
        public Prioridad $prioridad,
        string $texto,
        public ?int $consumoDeRespuesta = null,
        public ?int $consumoDeResolucion = null,
    ) {
        $this->texto = self::normalizar($texto);
    }

    // Palabra o frase completa, sin importar mayúsculas, tildes ni espacios de más: «vpn» está en
    // «La VPN falla», pero no en «vpnx»; «conexion» está en «sin conexión».
    public function menciona(string $palabras): bool
    {
        $buscadas = preg_quote(self::normalizar($palabras), '/');

        return preg_match("/(?<![\\p{L}\\p{N}]){$buscadas}(?![\\p{L}\\p{N}])/u", $this->texto) === 1;
    }

    private static function normalizar(string $texto): string
    {
        $unEspacio = preg_replace('/\s+/u', ' ', trim($texto)) ?? $texto;

        return strtr(mb_strtolower($unEspacio), self::SIN_TILDES);
    }
}
