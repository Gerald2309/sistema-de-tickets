<?php

namespace App\Incidentes\Acciones;

use App\Identidad\Models\User;
use App\Incidentes\Dominio\EntradaHistorial;
use App\Incidentes\Models\HistorialIncidente;
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

    /**
     * La misma entrada para muchos incidentes en un solo INSERT (un traspaso de miles no hace mil inserciones).
     *
     * @param  list<int>  $incidenteIds
     */
    public function enBloque(array $incidenteIds, User $autor, EntradaHistorial $entrada): void
    {
        $ahora = now();
        HistorialIncidente::insert(array_map(fn (int $id) => [
            'incidente_id' => $id,
            'usuario_id' => $autor->id,
            'evento' => $entrada->evento->value,
            'campo' => $entrada->campo?->value,
            'valor_anterior' => $entrada->valorAnterior,
            'valor_nuevo' => $entrada->valorNuevo,
            'created_at' => $ahora,
        ], $incidenteIds));
    }
}
