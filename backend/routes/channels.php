<?php

use App\Identidad\Dominio\ClavePermiso;
use App\Identidad\Models\User;
use Illuminate\Support\Facades\Broadcast;

// Canales privados del tiempo real (F-12). Por ellos solo viaja qué incidentes cambiaron; los datos se
// vuelven a pedir a la API, que aplica la visibilidad de siempre.

// Quien ve todos los incidentes escucha todos los cambios.
Broadcast::channel('incidentes', fn (User $usuario) => $usuario->permisos()->tiene(ClavePermiso::VerTodos));

// Cada persona escucha los cambios de los incidentes que creó o tiene asignados.
// El id llega como texto desde el nombre del canal: uno que no sea un número se rechaza sin error.
Broadcast::channel('usuario.{id}', fn (User $usuario, string $id) => ctype_digit($id) && $usuario->id === (int) $id);
