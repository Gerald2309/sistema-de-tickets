<?php

namespace App\Incidentes\Http\Requests;

use App\Identidad\Models\User;
use App\Incidentes\Dominio\AlcanceIncidentes;
use App\Incidentes\Dominio\Categoria;
use App\Incidentes\Dominio\Estado;
use App\Incidentes\Dominio\Prioridad;
use App\Incidentes\Policies\IncidentePolicy;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;

// Reglas comunes de crear y actualizar. Solo los campos listados llegan a la acción: el creador,
// el estado inicial y cualquier otro dato enviado se descartan.
abstract class IncidenteRequest extends FormRequest
{
    // ISO 8601 con zona horaria: con desfase (2026-10-30T15:00:00-05:00) o en UTC con la letra Z (2026-10-30T20:00:00Z).
    public const FORMATOS_FECHA = 'Y-m-d\TH:i:sP,Y-m-d\TH:i:s\Z';

    /**
     * @param  string  $presencia  'required' al crear; 'sometimes' al actualizar.
     * @return array<string, list<mixed>>
     */
    protected function reglasComunes(string $presencia): array
    {
        return [
            'titulo' => [$presencia, 'string', 'max:255'],
            'descripcion' => [$presencia, 'string', 'max:10000'],
            'prioridad' => ['sometimes', Rule::enum(Prioridad::class)],
            'categoria' => ['sometimes', Rule::enum(Categoria::class)],
            'fecha_vencimiento' => [$presencia, 'date_format:'.self::FORMATOS_FECHA],
            'usuario_asignado_id' => [
                'bail',
                'sometimes',
                'nullable',
                'integer',
                Rule::exists('users', 'id')->where('activo', true),
                // Un solicitante no atiende incidentes: asignárselo lo dejaría sin nadie que lo trabaje.
                function (string $atributo, mixed $valor, Closure $fallar): void {
                    if (! User::query()->atiendenIncidentes()->whereKey($valor)->exists()) {
                        $fallar('Solo se puede asignar a alguien que atiende incidentes.');
                    }
                },
            ],
        ];
    }

    /**
     * Cambiar el estado y escribir la solución, que solo se registra al resolver (la acción lo comprueba).
     *
     * @return array<string, list<mixed>>
     */
    protected function reglasDeEstado(): array
    {
        return [
            'estado' => ['sometimes', Rule::enum(Estado::class)],
            'solucion' => ['sometimes', 'required', 'string', 'max:10000'],
        ];
    }

    /**
     * Datos validados, con el vencimiento ya en UTC: un valor con otra zona se guardaría con su hora local.
     *
     * @return array<string, mixed>
     */
    public function datos(): array
    {
        $datos = $this->validated();

        if (isset($datos['fecha_vencimiento'])) {
            $datos['fecha_vencimiento'] = Carbon::parse($datos['fecha_vencimiento'])->utc();
        }

        return $datos;
    }

    protected function alcance(): AlcanceIncidentes
    {
        return IncidentePolicy::alcanceDe($this->user());
    }
}
