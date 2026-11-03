<?php

namespace App\Automatizaciones\Dominio;

use App\Incidentes\Dominio\Categoria;
use App\Incidentes\Dominio\Prioridad;

// Clase de dato que lleva una acción. Aquí se revisa la forma; que la persona o el rol existan lo revisa
// la petición, que sí consulta la base de datos.
enum DatoDeAccion
{
    case Id;
    case Plantilla;
    case Destinatario;
    case Prioridad;
    case Categoria;

    public const DESTINATARIOS = ['rol', 'usuario'];

    // Qué está mal con ese valor, o null si sirve.
    public function problemaCon(mixed $valor): ?string
    {
        return match ($this) {
            self::Id => is_int($valor) && $valor > 0 ? null : 'Elija a quién.',
            self::Plantilla => self::problemaConPlantilla($valor),
            self::Destinatario => in_array($valor, self::DESTINATARIOS, true) ? null : 'Debe ser un rol o una persona.',
            self::Prioridad => is_string($valor) && Prioridad::tryFrom($valor) ? null : 'Elija una prioridad.',
            self::Categoria => is_string($valor) && Categoria::tryFrom($valor) ? null : 'Elija una categoría.',
        };
    }

    private static function problemaConPlantilla(mixed $valor): ?string
    {
        if (! is_string($valor) || trim($valor) === '') {
            return 'Escriba el texto.';
        }

        if (mb_strlen($valor) > PlantillaDeMensaje::LONGITUD_MAXIMA) {
            return 'Admite como máximo '.PlantillaDeMensaje::LONGITUD_MAXIMA.' caracteres.';
        }

        $desconocidas = PlantillaDeMensaje::desconocidas($valor);

        return $desconocidas === [] ? null : sprintf(
            'No existe {%s}. Puede usar: {%s}.',
            implode('}, {', $desconocidas),
            implode('}, {', PlantillaDeMensaje::VARIABLES),
        );
    }
}
