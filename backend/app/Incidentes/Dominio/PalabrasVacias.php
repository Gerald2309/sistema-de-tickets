<?php

namespace App\Incidentes\Dominio;

// Palabras tan comunes en español que no distinguen un incidente de otro (artículos, preposiciones,
// conjunciones, pronombres y verbos auxiliares). El índice de texto de MySQL solo trae una lista en
// inglés, así que las del español se descartan aquí antes de buscar parecidos. Todas llevan 3 o más
// letras: las más cortas ya las descarta TerminosDeBusqueda.
final class PalabrasVacias
{
    private const LISTA = [
        'una', 'uno', 'unos', 'unas', 'los', 'las', 'del', 'por', 'para', 'con', 'sin', 'sobre', 'entre',
        'desde', 'hasta', 'hacia', 'ante', 'bajo', 'tras', 'según', 'segun', 'durante', 'mediante',
        'que', 'como', 'pero', 'sino', 'aunque', 'porque', 'pues', 'cuando', 'donde', 'cual', 'cuales',
        'quien', 'quienes', 'cuyo', 'este', 'esta', 'estos', 'estas', 'ese', 'esa', 'esos', 'esas',
        'aquel', 'aquella', 'aquellos', 'aquellas', 'esto', 'eso', 'aquello', 'mis', 'tus', 'sus',
        'nuestro', 'nuestra', 'nuestros', 'nuestras', 'muy', 'más', 'mas', 'menos', 'ahora', 'aquí', 'aqui',
        'allí', 'alli', 'hoy', 'ayer', 'también', 'tambien', 'solo', 'sólo', 'todo', 'toda', 'todos',
        'todas', 'otro', 'otra', 'otros', 'otras', 'cada', 'mismo', 'misma', 'mismos', 'mismas',
        'son', 'ser', 'sea', 'fue', 'era', 'han', 'hay', 'había', 'habia', 'está', 'están', 'estan',
        'estar', 'estoy', 'tiene', 'tienen', 'tengo', 'tener', 'hace', 'hacer', 'puede', 'pueden', 'puedo',
        'poder', 'favor', 'necesito', 'necesita', 'necesitamos', 'quiero', 'quiere',
    ];

    public static function esVacia(string $palabra): bool
    {
        return in_array(mb_strtolower($palabra), self::LISTA, true);
    }

    /**
     * @return list<string>
     */
    public static function todas(): array
    {
        return self::LISTA;
    }
}
