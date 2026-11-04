<?php

namespace App\Identidad\Models;

use App\Identidad\Dominio\ClavePermiso;
use App\Identidad\Dominio\ConjuntoPermisos;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

/**
 * @property-read Rol $rol todo usuario tiene rol (rol_id no admite nulos)
 * @property-read Cliente|null $cliente empresa cliente; nula para el personal de Psicol
 */
#[Fillable(['name', 'email', 'password', 'activo', 'rol_id', 'cliente_id', 'debe_cambiar_contrasena'])]
#[Hidden(['password'])]
#[UseFactory(UserFactory::class)]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    private ?ConjuntoPermisos $conjuntoDePermisos = null;

    /**
     * @return BelongsTo<Rol, $this>
     */
    public function rol(): BelongsTo
    {
        return $this->belongsTo(Rol::class, 'rol_id');
    }

    // Sus avisos llegan por el mismo canal privado que los cambios de sus incidentes (routes/channels.php).
    public function receivesBroadcastNotificationsOn(): string
    {
        return "usuario.{$this->id}";
    }

    /**
     * @return BelongsTo<Cliente, $this>
     */
    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class, 'cliente_id');
    }

    /**
     * Permisos del rol del usuario. Se leen una vez por petición (una consulta) y no se guardan entre peticiones.
     */
    public function permisos(): ConjuntoPermisos
    {
        return $this->conjuntoDePermisos ??= $this->loadMissing('rol.permisos')->rol->conjuntoDePermisos();
    }

    /**
     * Quien atiende incidentes: su rol puede cambiarles el estado. Solo a esas personas se les asigna uno.
     *
     * @param  Builder<self>  $consulta
     */
    public function scopeAtiendenIncidentes(Builder $consulta): void
    {
        $consulta->whereHas('rol.permisos', fn (Builder $permisos) => $permisos->where('clave', ClavePermiso::CambiarEstado->value));
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'activo' => 'boolean',
            'debe_cambiar_contrasena' => 'boolean',
            'ultimo_inicio_en' => 'immutable_datetime',
            'inicio_anterior_en' => 'immutable_datetime',
        ];
    }
}
