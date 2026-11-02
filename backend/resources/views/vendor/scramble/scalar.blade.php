<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $config->get('ui.title') }}</title>
</head>
<body>
<div id="app"></div>
{{-- Versión fija con integridad (SRI): si el CDN entrega otro archivo, el navegador no lo ejecuta. --}}
<script src="https://cdn.jsdelivr.net/npm/@scalar/api-reference@1.75.0/dist/browser/standalone.js"
        integrity="sha384-xgelKlDcc+BYp0O+ZYzFesLnWCgGpEbXVVZWNQT6WyZYygN01tbEMygnjPXb5OPI"
        crossorigin="anonymous"></script>
<script>
    Scalar.createApiReference('#app', {
        content: @json($spec),
        ...@json($config->renderer()->all(except: ['view'])),
    })
</script>
</body>
</html>
