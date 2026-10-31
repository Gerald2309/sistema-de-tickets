<?php

namespace App\Incidentes\Dominio;

// Campos del incidente cuyos cambios se registran: todos los que una persona puede modificar.
// El orden es el de las entradas cuando una misma petición cambia varios.
enum CampoHistorial: string
{
    case Titulo = 'titulo';
    case Descripcion = 'descripcion';
    case Estado = 'estado';
    case Solucion = 'solucion';
    case Prioridad = 'prioridad';
    case UsuarioAsignado = 'usuario_asignado';
    case FechaVencimiento = 'fecha_vencimiento';
    case Categoria = 'categoria';
}
