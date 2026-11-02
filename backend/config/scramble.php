<?php

use App\Http\Middleware\CabecerasDeDocumentacion;
use Dedoc\Scramble\Http\Middleware\RestrictedDocsAccess;
use Dedoc\Scramble\OpenApiVersion;

// Documentación de la API (OpenAPI 3.1), generada del código: rutas, validaciones (FormRequest) y respuestas
// (Resources). Se publica en /api/v1/documentacion y el contrato en /api/v1/openapi.json.
return [
    'api_path' => 'api/v1',

    'api_domain' => null,

    'export_path' => 'api.json',

    // Sin la caché de Scramble: el documento se exporta a un archivo al arrancar (DocumentacionController).
    'cache' => [
        'key' => null,
        'store' => null,
    ],

    'openapi_version' => OpenApiVersion::V3_1,

    'info' => [
        'version' => env('API_VERSION', '1.0.0'),
        'description' => <<<'MD'
            API de la mesa de ayuda de Psicol. Busque cualquier endpoint con **Ctrl + K**.

            - **Autenticación:** `POST /auth/login` entrega un token. Envíelo en cada petición como `Authorization: Bearer {token}`.
            - **Permisos:** cada endpoint dice qué exige. Lo que la persona no puede ver responde 404, no 403.
            - **Errores:** `application/problem+json` (RFC 9457). Los 422 agregan `errors`, con el mensaje de cada campo.
            - **Listados:** paginados con `page` y `per_page`.
            - **Límite:** 300 peticiones por minuto por persona (configurable). Al pasarlo, 429 con `Retry-After`.
            - **Reintentos:** crear un incidente o un mensaje acepta `Idempotency-Key` (UUID); repetirla no duplica nada.
            MD,
    ],

    'ui' => [
        'title' => 'Mesa de ayuda · API',
    ],

    'dev_tools' => [
        'enabled' => false,
    ],

    // Scalar: buscador (Ctrl + K), secciones plegables y prueba de peticiones. Versión fija con SRI en la vista
    // (resources/views/vendor/scramble/scalar.blade.php); sin fuentes, proxy ni servicios externos de Scalar.
    'renderer' => 'scalar',

    'renderers' => [
        'scalar' => [
            'view' => 'scramble::scalar',
            'layout' => 'modern',
            'theme' => 'default',
            'withDefaultFonts' => false,
            'hideModels' => true,
            'hideClientButton' => true,
            'hideDownloadButton' => false,
            'documentDownloadType' => 'json',
            'showDeveloperTools' => 'never',
            'agent' => ['disabled' => true],
            'defaultOpenAllTags' => false,
            'expandAllResponses' => false,
        ],
    ],

    'servers' => null,

    'enum_cases_description_strategy' => 'description',

    'enum_cases_names_strategy' => false,

    'flatten_deep_query_parameters' => true,

    'middleware' => [
        CabecerasDeDocumentacion::class,
        'throttle:api',
        RestrictedDocsAccess::class,
    ],

    'extensions' => [],

    // El contrato no es un secreto: la seguridad está en la autorización de cada ruta, no en esconderlas. Aun así,
    // se puede cerrar en un despliegue concreto.
    'publica' => (bool) env('DOCUMENTACION_API_PUBLICA', true),
];
