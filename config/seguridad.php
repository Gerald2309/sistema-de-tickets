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
    | Peticiones por minuto. El login se limita por correo (fuerza bruta contra una cuenta)
    | y por IP (rociado de correos desde un mismo origen).
    */
    'limites' => [
        'api' => env('LIMITE_API_POR_MINUTO', 60),
        'login_email' => env('LIMITE_LOGIN_POR_EMAIL', 5),
        'login_ip' => env('LIMITE_LOGIN_POR_IP', 30),
    ],

];
