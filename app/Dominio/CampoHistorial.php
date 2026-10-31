<?php

namespace App\Dominio;

// Campos del incidente cuyos cambios se registran. La descripción queda fuera.
enum CampoHistorial: string
{
    case Titulo = 'titulo';
    case Estado = 'estado';
    case Prioridad = 'prioridad';
    case UsuarioAsignado = 'usuario_asignado';
    case FechaVencimiento = 'fecha_vencimiento';
    case Categoria = 'categoria';
}
