<?php

namespace App\Incidentes\Models;

use App\Identidad\Models\Cliente;
use App\Identidad\Models\User;
use App\Incidentes\Dominio\AlcanceIncidentes;
use App\Incidentes\Dominio\Categoria;
use App\Incidentes\Dominio\Estado;
use App\Incidentes\Dominio\Participantes;
use App\Incidentes\Dominio\Prioridad;
use App\Incidentes\Dominio\Vencimiento;
use Closure;
use Database\Factories\IncidenteFactory;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Query\Builder as QueryBuilder;

/**
 * @property-read User $creador todo incidente tiene creador (usuario_creador_id no admite nulos)
 * @property-read User|null $asignado
 * @property-read Cliente|null $cliente empresa del solicitante al reportar
 */
#[Fillable([
    'titulo',
    'descripcion',
    'prioridad',
    'estado',
    'categoria',
    'solucion',
    'calificacion',
    'usuario_creador_id',
    'usuario_asignado_id',
    'cliente_id',
    'clave_de_idempotencia',
    'fecha_vencimiento',
    'fecha_requerida',
    'vence_respuesta_en',
])]
#[UseFactory(IncidenteFactory::class)]
class Incidente extends Model
{
    /** @use HasFactory<IncidenteFactory> */
    use HasFactory, SoftDeletes;

    // Las relaciones que toda respuesta muestra se cargan de antemano (sin N+1).
    public const RELACIONES_VISIBLES = ['creador:id,name', 'asignado:id,name', 'cliente:id,nombre'];

    protected $with = self::RELACIONES_VISIBLES;

    /**
     * Deja solo los incidentes que ese alcance puede ver. Es la misma regla que aplica la Policy,
     * expresada como consulta: sin permisos de lectura no devuelve nada.
     *
     * @param  Builder<Incidente>  $consulta
     */
    public function scopeVisiblePara(Builder $consulta, AlcanceIncidentes $alcance): void
    {
        match (true) {
            $alcance->veTodo() => null,
            $alcance->veSoloLoPropio() => $consulta->where('usuario_creador_id', $alcance->usuarioId()),
            default => $consulta->whereRaw('1 = 0'),
        };
    }

    /**
     * Los vencidos en ese instante (D-04): sin resolver y con el vencimiento ya pasado. Es la regla de
     * Vencimiento expresada como consulta; la usan el listado y el dashboard.
     *
     * @param  Builder<Incidente>  $consulta
     */
    public function scopeVencidosAl(Builder $consulta, DateTimeInterface $instante): void
    {
        $consulta->whereIn('estado', array_column(Vencimiento::estadosQueVencen(), 'value'))
            ->where('fecha_vencimiento', '<', $instante);
    }

    /**
     * Cuenta, en `mensajes_sin_leer`, los mensajes que esa persona puede leer, escritos por otra y posteriores
     * a su última lectura. Para `withCount` (listado) y `loadCount` (detalle).
     *
     * @return array<string, Closure(Builder<MensajeIncidente>): void>
     */
    public static function conteoDeMensajesSinLeer(int $lectorId, AlcanceIncidentes $alcance): array
    {
        $sinLeer = function (Builder $mensajes) use ($lectorId, $alcance): void {
            /** @var Builder<MensajeIncidente> $mensajes */
            $mensajes->visiblesPara($alcance)
                ->where('usuario_id', '!=', $lectorId)
                ->whereNotExists(fn (QueryBuilder $leidos) => $leidos->from('lecturas_conversacion')
                    ->whereColumn('lecturas_conversacion.incidente_id', 'mensajes_incidente.incidente_id')
                    ->where('lecturas_conversacion.usuario_id', $lectorId)
                    ->whereColumn('lecturas_conversacion.ultimo_mensaje_leido_id', '>=', 'mensajes_incidente.id'));
        };

        return ['mensajes as mensajes_sin_leer' => $sinLeer];
    }

    /**
     * Columnas que «Mi día» muestra de un incidente, con el motivo de la marca de «bloqueado» que le puso esa
     * persona (en `bloqueo`, null si no tiene). Sin las relaciones que cargan las demás respuestas.
     *
     * @param  Builder<Incidente>  $consulta
     */
    public function scopeParaElPlanDe(Builder $consulta, int $usuarioId): void
    {
        $consulta->withoutEagerLoads()
            ->select(['incidentes.id', 'titulo', 'prioridad', 'estado', 'fecha_vencimiento', 'usuario_creador_id', 'usuario_asignado_id'])
            ->selectSub(fn (QueryBuilder $bloqueos) => $bloqueos->from('bloqueos_personales')
                ->select('motivo')
                ->whereColumn('bloqueos_personales.incidente_id', 'incidentes.id')
                ->where('bloqueos_personales.usuario_id', $usuarioId), 'bloqueo');
    }

    public function participantes(): Participantes
    {
        return new Participantes($this->usuario_creador_id, $this->usuario_asignado_id);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_creador_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function asignado(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_asignado_id');
    }

    /**
     * @return BelongsTo<Cliente, $this>
     */
    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class, 'cliente_id');
    }

    /**
     * @return HasMany<HistorialIncidente, $this>
     */
    public function historial(): HasMany
    {
        return $this->hasMany(HistorialIncidente::class, 'incidente_id');
    }

    /**
     * @return HasMany<MensajeIncidente, $this>
     */
    public function mensajes(): HasMany
    {
        return $this->hasMany(MensajeIncidente::class, 'incidente_id');
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
            'fecha_requerida' => 'datetime',
            'resuelto_en' => 'datetime',
            'vence_respuesta_en' => 'datetime',
            'primera_respuesta_en' => 'datetime',
            'calificacion' => 'integer',
        ];
    }
}
