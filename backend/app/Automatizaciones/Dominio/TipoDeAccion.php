<?php

namespace App\Automatizaciones\Dominio;

// Lo que una automatización puede hacer con un incidente.
enum TipoDeAccion: string
{
    case Asignar = 'asignar';
    case Responder = 'responder';
    case Avisar = 'avisar';
    case CambiarPrioridad = 'cambiar_prioridad';
    case CambiarCategoria = 'cambiar_categoria';
    case NotaInterna = 'nota_interna';
    case Cerrar = 'cerrar';

    /**
     * Datos que necesita, con la clase de cada uno.
     *
     * @return non-empty-array<string, DatoDeAccion>
     */
    public function datos(): array
    {
        return match ($this) {
            self::Asignar => ['usuario_id' => DatoDeAccion::Id],
            self::Responder, self::Cerrar => ['mensaje' => DatoDeAccion::Plantilla],
            self::Avisar => ['destinatario' => DatoDeAccion::Destinatario, 'id' => DatoDeAccion::Id],
            self::CambiarPrioridad => ['prioridad' => DatoDeAccion::Prioridad],
            self::CambiarCategoria => ['categoria' => DatoDeAccion::Categoria],
            self::NotaInterna => ['texto' => DatoDeAccion::Plantilla],
        };
    }
}
