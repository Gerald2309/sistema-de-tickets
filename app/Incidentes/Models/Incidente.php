<?php

namespace App\Incidentes\Models;

use App\Identidad\Models\User;
use App\Incidentes\Dominio\Categoria;
use App\Incidentes\Dominio\Estado;
use App\Incidentes\Dominio\Prioridad;
use Database\Factories\IncidenteFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
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
#[UseFactory(IncidenteFactory::class)]
class Incidente extends Model
{
    /** @use HasFactory<IncidenteFactory> */
    use HasFactory, SoftDeletes;

    // Las relaciones que toda respuesta muestra se cargan de antemano (sin N+1).
    public const RELACIONES_VISIBLES = ['creador:id,name', 'asignado:id,name'];

    protected $with = self::RELACIONES_VISIBLES;

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
