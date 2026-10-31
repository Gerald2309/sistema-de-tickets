<?php

// Solo las reglas que la API usa.
return [
    'required' => 'El campo :attribute es obligatorio.',
    'string' => 'El campo :attribute debe ser texto.',
    'email' => 'El campo :attribute debe ser un correo electrónico válido.',
    'max' => [
        'string' => 'El campo :attribute no puede superar los :max caracteres.',
    ],
    'min' => [
        'string' => 'El campo :attribute debe tener al menos :min caracteres.',
    ],

    'attributes' => [
        'email' => 'correo electrónico',
        'password' => 'contraseña',
        'password_actual' => 'contraseña actual',
    ],
];
