<?php

// Solo las reglas que la API usa.
return [
    'required' => 'El campo :attribute es obligatorio.',
    'string' => 'El campo :attribute debe ser texto.',
    'integer' => 'El campo :attribute debe ser un número entero.',
    'email' => 'El campo :attribute debe ser un correo electrónico válido.',
    'enum' => 'El valor del campo :attribute no es válido.',
    'exists' => 'El valor del campo :attribute no existe o no está activo.',
    'date_format' => 'El campo :attribute debe tener el formato AAAA-MM-DDThh:mm:ss con zona horaria (ISO 8601), por ejemplo 2026-10-30T15:00:00-05:00.',
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
        'titulo' => 'título',
        'descripcion' => 'descripción',
        'prioridad' => 'prioridad',
        'categoria' => 'categoría',
        'estado' => 'estado',
        'solucion' => 'solución',
        'fecha_vencimiento' => 'fecha de vencimiento',
        'usuario_asignado_id' => 'usuario asignado',
    ],
];
