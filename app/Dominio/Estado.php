<?php

namespace App\Dominio;

// Declarado en el orden del ciclo de vida: el ENUM de MySQL ordena por posición.
enum Estado: string
{
    case Abierto = 'abierto';
    case EnProgreso = 'en_progreso';
    case Resuelto = 'resuelto';
    case Cerrado = 'cerrado';
}
