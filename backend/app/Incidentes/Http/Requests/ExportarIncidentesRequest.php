<?php

namespace App\Incidentes\Http\Requests;

use App\Incidentes\Dominio\FormatoDeExportacion;
use Illuminate\Support\Arr;
use Illuminate\Validation\Rule;

// Los mismos filtros y orden que el listado, sin paginación, más el formato del archivo.
class ExportarIncidentesRequest extends ListarIncidentesRequest
{
    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return Arr::except(parent::rules(), ['page', 'per_page']) + [
            'formato' => ['required', Rule::enum(FormatoDeExportacion::class)],
        ];
    }

    public function formato(): FormatoDeExportacion
    {
        return FormatoDeExportacion::from($this->string('formato')->toString());
    }
}
