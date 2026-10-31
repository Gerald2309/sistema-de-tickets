<?php

// Solo las reglas que la API usa.
return [
    'required' => 'El campo :attribute es obligatorio.',
    'string' => 'El campo :attribute debe ser texto.',
    'integer' => 'El campo :attribute debe ser un número entero.',
    'email' => 'El campo :attribute debe ser un correo electrónico válido.',
    'in' => 'El valor del campo :attribute no es válido.',
    'after_or_equal' => 'El campo :attribute debe ser una fecha igual o posterior a :date.',
    'enum' => 'El valor del campo :attribute no es válido.',
    'exists' => 'El valor del campo :attribute no existe o no está activo.',
    'date_format' => 'El campo :attribute debe tener el formato AAAA-MM-DDThh:mm:ss con zona horaria (ISO 8601), por ejemplo 2026-10-30T15:00:00-05:00.',
    'max' => [
        'string' => 'El campo :attribute no puede superar los :max caracteres.',
    ],
    'between' => [
        'numeric' => 'El campo :attribute debe estar entre :min y :max.',
    ],
    'min' => [
        'numeric' => 'El campo :attribute debe ser al menos :min.',
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
        'q' => 'búsqueda',
        'fecha_desde' => 'fecha desde',
        'fecha_hasta' => 'fecha hasta',
        'campo_fecha' => 'campo de fecha',
        'ordenar_por' => 'orden',
        'direccion' => 'dirección',
        'page' => 'página',
        'per_page' => 'resultados por página',
    ],
];
