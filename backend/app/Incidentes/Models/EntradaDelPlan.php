<?php

namespace App\Incidentes\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Algo que una persona planea hacer un día a una hora: un incidente o una nota suya (nunca los dos).
 *
 * @property Carbon $fecha
 * @property-read Incidente|null $incidente
 * @property-read NotaPersonal|null $nota
 */
#[Fillable(['usuario_id', 'fecha', 'inicio', 'duracion', 'incidente_id', 'nota_id'])]
class EntradaDelPlan extends Model
{
    protected $table = 'entradas_del_plan';

    /**
     * @param  Builder<EntradaDelPlan>  $consulta
     */
    public function scopeDe(Builder $consulta, int $usuarioId): void
    {
        $consulta->where('usuario_id', $usuarioId);
    }

    public function fin(): int
    {
        return $this->inicio + $this->duracion;
    }

    /**
     * @return BelongsTo<Incidente, $this>
     */
    public function incidente(): BelongsTo
    {
        return $this->belongsTo(Incidente::class, 'incidente_id');
    }

    /**
     * @return BelongsTo<NotaPersonal, $this>
     */
    public function nota(): BelongsTo
    {
        return $this->belongsTo(NotaPersonal::class, 'nota_id');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['fecha' => 'date', 'inicio' => 'integer', 'duracion' => 'integer'];
    }
}
