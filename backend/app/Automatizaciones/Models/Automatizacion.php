<?php

namespace App\Automatizaciones\Models;

use App\Automatizaciones\Dominio\Disparador;
use App\Automatizaciones\Dominio\Flujo;
use App\Automatizaciones\Dominio\LectorDeFlujo;
use App\Identidad\Models\User;
use Database\Factories\AutomatizacionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string $nombre
 * @property bool $activa
 * @property Disparador $disparador
 * @property list<array<string, mixed>> $pasos tal como los dejó LectorDeFlujo al guardarlos
 * @property-read User $autor quien la creó o la editó por última vez; lo que hace queda a su nombre
 */
#[Fillable(['nombre', 'activa', 'disparador', 'pasos', 'autor_id'])]
#[UseFactory(AutomatizacionFactory::class)]
class Automatizacion extends Model
{
    /** @use HasFactory<AutomatizacionFactory> */
    use HasFactory;

    protected $table = 'automatizaciones';

    public function flujo(): Flujo
    {
        return LectorDeFlujo::leer($this->pasos);
    }

    /**
     * Las que están encendidas para ese disparador, en el orden en que se crearon.
     *
     * @param  Builder<self>  $consulta
     */
    public function scopeActivasPara(Builder $consulta, Disparador $disparador): void
    {
        $consulta->where('disparador', $disparador)->where('activa', true)->orderBy('id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function autor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'autor_id');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'activa' => 'boolean',
            'disparador' => Disparador::class,
            'pasos' => 'array',
        ];
    }
}
