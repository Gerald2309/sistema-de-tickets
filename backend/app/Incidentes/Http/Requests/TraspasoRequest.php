<?php

namespace App\Incidentes\Http\Requests;

use App\Identidad\Models\User;
use Closure;
use Illuminate\Foundation\Http\FormRequest;

// A quién pasan los incidentes abiertos de una persona: a otra que atiende incidentes, o null para devolverlos a
// la cola sin asignar. Se pide siempre explícito: no hay valor por omisión en la API.
class TraspasoRequest extends FormRequest
{
    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            'usuario_asignado_id' => ['present', 'nullable', 'integer', $this->destinoValido(...)],
        ];
    }

    private function destinoValido(string $atributo, mixed $id, Closure $fallar): void
    {
        /** @var User $origen */
        $origen = $this->route('usuario');

        if ((int) $id === $origen->id) {
            $fallar(__('incidentes.traspaso_a_la_misma_persona'));

            return;
        }
        if (! User::query()->atiendenIncidentes()->where('activo', true)->whereKey($id)->exists()) {
            $fallar(__('incidentes.solo_a_quien_atiende'));
        }
    }

    public function destino(): ?User
    {
        $id = $this->validated('usuario_asignado_id');

        return $id === null ? null : User::findOrFail((int) $id);
    }
}
