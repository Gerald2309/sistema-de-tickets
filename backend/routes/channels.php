<?php

use App\Identidad\Dominio\ClavePermiso;
use App\Identidad\Models\User;
use Illuminate\Support\Facades\Broadcast;

// Canales privados del tiempo real (F-12). Por ellos solo viaja qué incidentes cambiaron; los datos se
// vuelven a pedir a la API, que aplica la visibilidad de siempre.

// Quien ve todos los incidentes escucha todos los cambios.
Broadcast::channel('incidentes', fn (User $usuario) => $usuario->permisos()->tiene(ClavePermiso::VerTodos));

// Cada persona escucha los cambios de los incidentes que creó o tiene asignados.
Broadcast::channel('usuario.{id}', fn (User $usuario, int $id) => $usuario->id === $id);
