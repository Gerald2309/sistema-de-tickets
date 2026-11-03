<?php

namespace App\Automatizaciones\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

/**
 * Una automatización aplicada a un incidente: cuántas acciones hizo y, si se detuvo, por qué.
 *
 * @property int $acciones_hechas
 * @property string|null $error
 */
#[Fillable(['automatizacion_id', 'incidente_id', 'acciones_hechas', 'error'])]
class EjecucionDeAutomatizacion extends Model
{
    public const UPDATED_AT = null;

    protected $table = 'ejecuciones_de_automatizacion';
}
