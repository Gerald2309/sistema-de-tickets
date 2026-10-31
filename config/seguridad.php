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

];
