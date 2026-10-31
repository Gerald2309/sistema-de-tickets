<?php

return [

    /*
    | Primer administrador del sistema, creado por el seeder. Las credenciales salen
    | del entorno: no hay ninguna contraseña escrita en el repositorio.
    */
    'administrador' => [
        'nombre' => env('ADMIN_NOMBRE'),
        'email' => env('ADMIN_EMAIL'),
        'password' => env('ADMIN_PASSWORD'),
    ],

    /*
    | Cuentas de prueba de la demostración, una por perfil: comparten esta contraseña para que
    | quien evalúe pueda entrar con cada rol. Sin ella, también se crean con contraseña aleatoria.
    */
    'demostracion' => [
        'password' => env('DEMO_PASSWORD'),
    ],

    /*
    | Peticiones por minuto. El login se limita por correo (fuerza bruta contra una cuenta)
    | y por IP (rociado de correos desde un mismo origen).
    */
    /*
    | Búsqueda de texto: longitud mínima de una palabra (el índice de MySQL no guarda las de menos
    | de 3 letras) y máximo de palabras que se combinan en una búsqueda.
    */
    'busqueda' => [
        'min_token' => 3,
        'max_terminos' => 5,
    ],

    // Direcciones del proxy que termina el TLS (separadas por comas, o `*`). Vacío: no se confía en ninguna.
    'proxies_confiables' => env('TRUSTED_PROXIES', ''),

    // Sesiones abiertas a la vez por persona: al iniciar otra, se cierran las más antiguas.
    'sesiones_maximas' => (int) env('SESIONES_MAXIMAS', 5),

    'limites' => [
        // Por persona. La aplicación web pide de 6 a 8 recursos por pantalla (listas, totales, detalle):
        // 300 por minuto deja navegar con soltura y sigue frenando un uso abusivo.
        'api' => env('LIMITE_API_POR_MINUTO', 300),
        'login_email' => env('LIMITE_LOGIN_POR_EMAIL', 5),
        'login_ip' => env('LIMITE_LOGIN_POR_IP', 30),
    ],

];
