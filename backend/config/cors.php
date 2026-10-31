<?php

return [

    /*
    | CORS: solo la API, y solo para los orígenes de CORS_ALLOWED_ORIGINS (lista separada por comas).
    | Vacía no se abre a ningún navegador de otro origen; nunca se usa "*". Con tokens Bearer no hay
    | cookies, así que no se admiten credenciales.
    */

    'paths' => ['api/*'],

    'allowed_methods' => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'],

    'allowed_origins' => array_values(array_filter(array_map('trim', explode(',', (string) env('CORS_ALLOWED_ORIGINS', ''))))),

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['Authorization', 'Content-Type', 'Accept'],

    'exposed_headers' => ['Retry-After', 'X-RateLimit-Limit', 'X-RateLimit-Remaining'],

    'max_age' => 600,

    'supports_credentials' => false,

];
