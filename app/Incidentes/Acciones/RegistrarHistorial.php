<?php

namespace App\Incidentes\Acciones;

use App\Identidad\Models\User;
use App\Incidentes\Dominio\EntradaHistorial;
use App\Incidentes\Models\Incidente;

// Única pieza que traduce las entradas del dominio a filas de historial. Se llama dentro de la
// transacción de cada Acción, para que el cambio y su registro se confirmen juntos.
class RegistrarHistorial
{
    /**
     * @param  list<EntradaHistorial>  $entradas
     */
    public function ejecutar(Incidente $incidente, User $autor, array $entradas): void
    {
        $incidente->historial()->createMany(array_map(fn (EntradaHistorial $entrada) => [
            'usuario_id' => $autor->id,
            'evento' => $entrada->evento,
            'campo' => $entrada->campo,
            'valor_anterior' => $entrada->valorAnterior,
            'valor_nuevo' => $entrada->valorNuevo,
        ], $entradas));
    }
}
