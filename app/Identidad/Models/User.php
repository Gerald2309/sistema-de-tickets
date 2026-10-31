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

#[Fillable(['name', 'email', 'password', 'activo', 'rol_id'])]
#[Hidden(['password'])]
#[UseFactory(UserFactory::class)]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory;

    private ?ConjuntoPermisos $conjuntoDePermisos = null;

    public function rol(): BelongsTo
    {
        return $this->belongsTo(Rol::class, 'rol_id');
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
