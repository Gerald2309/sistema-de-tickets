<?php

namespace App\Auditoria\Models;

use App\Auditoria\Dominio\AccionAuditoria;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

/**
 * @property string|null $usuario_nombre nombre de quien actuó; solo viene de ListarAuditoria (join con users)
 * @property string|null $usuario_afectado_nombre nombre del afectado; solo viene de ListarAuditoria
 */
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
