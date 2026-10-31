<?php

namespace App\Models;

use App\Dominio\Categoria;
use App\Dominio\Estado;
use App\Dominio\Prioridad;
use Database\Factories\IncidenteFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'titulo',
    'descripcion',
    'prioridad',
    'estado',
    'categoria',
    'solucion',
    'usuario_creador_id',
    'usuario_asignado_id',
    'fecha_vencimiento',
])]
class Incidente extends Model
{
    /** @use HasFactory<IncidenteFactory> */
    use HasFactory, SoftDeletes;

    public function creador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_creador_id');
    }

    public function asignado(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_asignado_id');
    }

    public function historial(): HasMany
    {
        return $this->hasMany(HistorialIncidente::class, 'incidente_id');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'prioridad' => Prioridad::class,
            'estado' => Estado::class,
            'categoria' => Categoria::class,
            'fecha_vencimiento' => 'datetime',
        ];
    }
}
