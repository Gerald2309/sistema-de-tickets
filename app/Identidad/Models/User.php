<?php

namespace App\Identidad\Models;

use App\Identidad\Dominio\ConjuntoPermisos;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;

/**
 * @property-read Rol $rol todo usuario tiene rol (rol_id no admite nulos)
 * @property-read Cliente|null $cliente empresa cliente; nula para el personal de Psicol
 */
#[Fillable(['name', 'email', 'password', 'activo', 'rol_id', 'cliente_id'])]
#[Hidden(['password'])]
#[UseFactory(UserFactory::class)]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory;

    private ?ConjuntoPermisos $conjuntoDePermisos = null;

    /**
     * @return BelongsTo<Rol, $this>
     */
    public function rol(): BelongsTo
    {
        return $this->belongsTo(Rol::class, 'rol_id');
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
        return $this->conjuntoDePermisos ??= new ConjuntoPermisos(
            $this->loadMissing('rol.permisos')->rol->permisos->map(fn (Permiso $permiso) => $permiso->clave),
        );
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'activo' => 'boolean',
        ];
    }
}
