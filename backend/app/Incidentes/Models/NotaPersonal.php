<?php

namespace App\Incidentes\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Nota libre de «Mi día». Es de una sola persona: solo ella la ve.
 *
 * @property-read Incidente|null $incidente el incidente que menciona, si la persona lo ve
 * @property-read EntradaDelPlan|null $entrada dónde quedó en la agenda, si se le dio hora
 */
#[Fillable(['usuario_id', 'texto', 'incidente_id', 'hecha'])]
class NotaPersonal extends Model
{
    public const MAXIMO_TEXTO = 200;

    protected $table = 'notas_personales';

    /**
     * @param  Builder<NotaPersonal>  $consulta
     */
    public function scopeDe(Builder $consulta, int $usuarioId): void
    {
        $consulta->where('usuario_id', $usuarioId);
    }

    /**
     * @return BelongsTo<Incidente, $this>
     */
    public function incidente(): BelongsTo
    {
        return $this->belongsTo(Incidente::class, 'incidente_id');
    }

    /**
     * @return HasOne<EntradaDelPlan, $this>
     */
    public function entrada(): HasOne
    {
        return $this->hasOne(EntradaDelPlan::class, 'nota_id');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['hecha' => 'boolean'];
    }
}
