<?php

namespace App\Models;

use App\Dominio\AccionAuditoria;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['usuario_id', 'accion', 'usuario_afectado_id', 'detalle', 'ip'])]
class Auditoria extends Model
{
    // Solo se inserta: la fila nunca se modifica.
    public const UPDATED_AT = null;

    protected $table = 'auditoria';

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    public function afectado(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_afectado_id');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'accion' => AccionAuditoria::class,
        ];
    }
}
