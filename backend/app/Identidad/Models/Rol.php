<?php

namespace App\Identidad\Models;

use App\Identidad\Dominio\ConjuntoPermisos;
use Database\Factories\RolFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['nombre'])]
#[UseFactory(RolFactory::class)]
class Rol extends Model
{
    /** @use HasFactory<RolFactory> */
    use HasFactory;

    protected $table = 'roles';

    /**
     * @return HasMany<User, $this>
     */
    public function usuarios(): HasMany
    {
        return $this->hasMany(User::class, 'rol_id');
    }

    /**
     * @return BelongsToMany<Permiso, $this>
     */
    public function permisos(): BelongsToMany
    {
        return $this->belongsToMany(Permiso::class, 'permiso_rol', 'rol_id', 'permiso_id');
    }

    public function conjuntoDePermisos(): ConjuntoPermisos
    {
        return new ConjuntoPermisos($this->loadMissing('permisos')->permisos->map(fn (Permiso $permiso) => $permiso->clave));
    }
}
