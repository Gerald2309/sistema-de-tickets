<?php

namespace App\Models;

use App\Dominio\CampoHistorial;
use App\Dominio\EventoHistorial;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['incidente_id', 'usuario_id', 'evento', 'campo', 'valor_anterior', 'valor_nuevo'])]
class HistorialIncidente extends Model
{
    // Solo se inserta: la fila nunca se modifica.
    public const UPDATED_AT = null;

    public function incidente(): BelongsTo
    {
        return $this->belongsTo(Incidente::class, 'incidente_id');
    }

    public function autor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'evento' => EventoHistorial::class,
            'campo' => CampoHistorial::class,
        ];
    }
}
