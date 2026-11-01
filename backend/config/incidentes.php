<?php

return [

    /*
    | Aviso de posible incidente masivo: cuando en las últimas `ventana_horas` horas hay al menos
    | `minimo_incidentes` incidentes sin resolver de una misma categoría, creados por al menos
    | `minimo_personas` personas distintas. Son valores a calibrar con la operación real.
    */
    'avisos' => [
        'ventana_horas' => (int) env('AVISO_VENTANA_HORAS', 2),
        'minimo_incidentes' => (int) env('AVISO_MINIMO_INCIDENTES', 5),
        'minimo_personas' => (int) env('AVISO_MINIMO_PERSONAS', 3),
        'maximo_palabras' => 5,
        // Cuántos incidentes recientes de la categoría se leen para sacar las palabras que se repiten.
        'maximo_textos' => 200,
    ],

    // Los plazos en horario laboral se cuentan en la zona horaria de la operación.
    'acuerdos' => [
        'zona_horaria' => env('ACUERDOS_ZONA_HORARIA', 'America/Bogota'),
    ],

    // Máximo de incidentes por descarga (CSV o Excel). Con más, hay que acotar los filtros.
    'exportacion' => [
        'maximo' => (int) env('EXPORTACION_MAXIMA', 100_000),
    ],

    // Cantidad de incidentes que genera VolumenSeeder para medir el rendimiento (máximo 1.000.000; la meta del proyecto es 200.000).
    'volumen' => [
        'incidentes' => (int) env('VOLUMEN_INCIDENTES', 200_000),
    ],

];
