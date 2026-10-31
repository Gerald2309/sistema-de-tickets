<?php

namespace App\Models;

use App\Dominio\ClavePermiso;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['clave'])]
class Permiso extends Model
{
    public $timestamps = false;

    protected $table = 'permisos';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'clave' => ClavePermiso::class,
        ];
    }
}
