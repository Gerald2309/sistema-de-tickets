<?php

namespace App\Incidentes\Models;

use App\Identidad\Models\User;
use App\Incidentes\Dominio\CampoHistorial;
use App\Incidentes\Dominio\EventoHistorial;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property-read User $autor todo cambio tiene autor (usuario_id no admite nulos)
 */
#[Fillable(['incidente_id', 'usuario_id', 'origen', 'evento', 'campo', 'valor_anterior', 'valor_nuevo'])]
class HistorialIncidente extends Model
{
    // Solo se inserta: la fila nunca se modifica.
    public const UPDATED_AT = null;

    /**
     * @return BelongsTo<Incidente, $this>
     */
    public function incidente(): BelongsTo
    {
        return $this->belongsTo(Incidente::class, 'incidente_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
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
