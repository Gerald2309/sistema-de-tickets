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
            API de la mesa de ayuda de Psicol: incidentes, su conversación e historial, usuarios, roles, empresas,
            acuerdos de servicio, panel y auditoría.

            **Autenticación.** `POST /auth/login` entrega un token; se envía en cada petición como
            `Authorization: Bearer {token}`. Expira según `SANCTUM_EXPIRATION`.

            **Errores.** Todos salen como `application/problem+json` (RFC 9457): `type`, `title`, `status` y `detail`.
            Los de validación (422) agregan `errors`, con los mensajes de cada campo. Un recurso que la persona no
            puede ver responde 404, no 403, para no revelar que existe.
            MD,
    ],

    'ui' => [
        'title' => 'Mesa de ayuda · API',
    ],

    'dev_tools' => [
        'enabled' => false,
    ],

    'renderer' => 'elements',

    'renderers' => [
        'elements' => [
            'view' => 'scramble::docs',
            'theme' => 'light',
            'hideTryIt' => false,
            'hideSchemas' => false,
            'logo' => '',
            'tryItCredentialsPolicy' => 'same-origin',
            'layout' => 'responsive',
            'router' => 'hash',
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
