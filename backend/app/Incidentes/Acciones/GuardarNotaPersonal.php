<?php

namespace App\Incidentes\Acciones;

use App\Identidad\Models\User;
use App\Incidentes\Dominio\CodigoIncidente;
use App\Incidentes\Models\Incidente;
use App\Incidentes\Models\NotaPersonal;
use App\Incidentes\Policies\IncidentePolicy;

class GuardarNotaPersonal
{
    /**
     * Crea o cambia una nota de esa persona. Si el texto menciona un código que ella ve (INC-000698), la nota
     * queda ligada a ese incidente.
     *
     * @param  array{texto?: string, hecha?: bool}  $campos
     */
    public function ejecutar(User $usuario, array $campos, ?NotaPersonal $nota = null): NotaPersonal
    {
        $nota ??= new NotaPersonal(['usuario_id' => $usuario->id]);
        $nota->fill($campos);
        if (array_key_exists('texto', $campos)) {
            $nota->fill(['incidente_id' => $this->incidenteMencionado($usuario, $campos['texto'])]);
        }
        $nota->save();

        return $nota->load(['incidente' => fn ($incidentes) => $incidentes->withoutEagerLoads()->select('incidentes.id')]);
    }

    private function incidenteMencionado(User $usuario, string $texto): ?int
    {
        if (preg_match('/\bINC-\d+\b/i', $texto, $mencion) !== 1) {
            return null;
        }

        $id = CodigoIncidente::aId($mencion[0]);

        return $id !== null && Incidente::visiblePara(IncidentePolicy::alcanceDe($usuario))->whereKey($id)->exists() ? $id : null;
    }
}
