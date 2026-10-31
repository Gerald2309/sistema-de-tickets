<?php

namespace App\Dominio;

// Lista fija de categorías para agrupar incidentes. Añadir una exige una migración (ENUM de MySQL).
enum Categoria: string
{
    case Red = 'red';
    case Correo = 'correo';
    case Hardware = 'hardware';
    case Software = 'software';
    case Accesos = 'accesos';
    case Seguridad = 'seguridad';
    case Telefonia = 'telefonia';
    case Otros = 'otros';
}
