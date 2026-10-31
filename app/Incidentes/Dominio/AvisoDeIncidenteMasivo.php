<?php

namespace App\Incidentes\Dominio;

use DateTimeInterface;

// Varias personas reportan lo mismo en poco tiempo: probablemente hay una causa común. No lleva datos de las personas.
final readonly class AvisoDeIncidenteMasivo
{
    /**
     * @param  int  $incidentes  incidentes sin resolver de esa categoría dentro de la ventana
     * @param  int  $personas  personas distintas que los crearon
     * @param  list<string>  $palabras  las que más se repiten en sus títulos y descripciones
     * @param  DateTimeInterface  $desde  inicio de la ventana de tiempo observada
     */
    public function __construct(
        public Categoria $categoria,
        public int $incidentes,
        public int $personas,
        public array $palabras,
        public DateTimeInterface $desde,
    ) {}
}
