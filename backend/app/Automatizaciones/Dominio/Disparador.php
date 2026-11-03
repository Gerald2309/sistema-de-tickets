<?php

namespace App\Automatizaciones\Dominio;

// Lo que pone en marcha una automatización: la creación de un incidente o que llegue un momento.
enum Disparador: string
{
    case Creacion = 'creacion';
    case SlaPorVencer = 'sla_por_vencer';
    case SlaVencido = 'sla_vencido';
    case SinRespuesta = 'sin_respuesta';
    case ResueltoSinConfirmar = 'resuelto_sin_confirmar';

    /**
     * Los que dependen del paso del tiempo: los revisa el programador de tareas.
     *
     * @return list<self>
     */
    public static function porTiempo(): array
    {
        return array_values(array_filter(self::cases(), fn (self $disparador) => $disparador !== self::Creacion));
    }
}
