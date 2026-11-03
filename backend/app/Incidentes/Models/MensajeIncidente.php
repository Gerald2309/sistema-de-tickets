<?php

namespace App\Incidentes\Models;

use App\Identidad\Models\User;
use App\Incidentes\Dominio\AlcanceIncidentes;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property-read User $autor todo mensaje tiene autor (usuario_id no admite nulos)
 * @property string|null $origen el proceso que lo escribió a nombre del autor (una automatización)
 */
#[Fillable(['incidente_id', 'usuario_id', 'origen', 'cuerpo', 'interno', 'clave_de_idempotencia'])]
class MensajeIncidente extends Model
{
    // Solo se inserta: un mensaje enviado no se modifica.
    public const UPDATED_AT = null;

    protected $table = 'mensajes_incidente';

    /**
     * Deja fuera las notas internas para quien no puede leerlas.
     *
     * @param  Builder<MensajeIncidente>  $consulta
     */
    public function scopeVisiblesPara(Builder $consulta, AlcanceIncidentes $alcance): void
    {
        $consulta->when(! $alcance->puedeLeerNotasInternas(), fn (Builder $publicos) => $publicos->where('interno', false));
    }

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
     * @return HasMany<AdjuntoMensaje, $this>
     */
    public function adjuntos(): HasMany
    {
        return $this->hasMany(AdjuntoMensaje::class, 'mensaje_id');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['interno' => 'boolean'];
    }
}
