<?php

// Umbrales a partir de los cuales una petición o una consulta se registra como lenta (nivel warning), para
// encontrarlas en los logs sin revisar cada línea.
return [
    'peticion_lenta_ms' => (int) env('OBSERVABILIDAD_PETICION_LENTA_MS', 1000),
    'consulta_lenta_ms' => (int) env('OBSERVABILIDAD_CONSULTA_LENTA_MS', 300),
];
