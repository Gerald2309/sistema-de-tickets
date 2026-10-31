<?php

namespace App\Auditoria\Models;

use App\Auditoria\Dominio\AccionAuditoria;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['usuario_id', 'accion', 'usuario_afectado_id', 'detalle', 'ip'])]
class Auditoria extends Model
{
    // Solo se inserta: la fila nunca se modifica.
    public const UPDATED_AT = null;

    protected $table = 'auditoria';

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
