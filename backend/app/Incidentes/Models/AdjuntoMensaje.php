<?php

namespace App\Incidentes\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

// Una imagen de un mensaje. El archivo vive en el disco privado con un nombre aleatorio; `nombre` es el
// que tenía en el equipo de quien la envió y solo se muestra.
#[Fillable(['mensaje_id', 'ruta', 'nombre', 'tipo', 'bytes'])]
class AdjuntoMensaje extends Model
{
    public $timestamps = false;

    protected $table = 'adjuntos_mensaje';

    /**
     * @return BelongsTo<MensajeIncidente, $this>
     */
    public function mensaje(): BelongsTo
    {
        return $this->belongsTo(MensajeIncidente::class, 'mensaje_id');
    }
}
