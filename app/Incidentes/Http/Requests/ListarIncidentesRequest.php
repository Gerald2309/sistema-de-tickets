<?php

namespace App\Incidentes\Http\Requests;

use App\Incidentes\Dominio\CampoFecha;
use App\Incidentes\Dominio\CampoOrden;
use App\Incidentes\Dominio\Categoria;
use App\Incidentes\Dominio\Direccion;
use App\Incidentes\Dominio\Estado;
use App\Incidentes\Dominio\FiltrosDeIncidentes;
use App\Incidentes\Dominio\Prioridad;
use App\Incidentes\Dominio\TerminosDeBusqueda;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;

class ListarIncidentesRequest extends FormRequest
{
    // Los permisos los comprueba la ruta (->can('listar')).
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            'q' => ['nullable', 'string', 'max:100'],
            'prioridad' => ['nullable', Rule::enum(Prioridad::class)],
            'estado' => ['nullable', Rule::enum(Estado::class)],
            'categoria' => ['nullable', Rule::enum(Categoria::class)],
            'usuario_asignado_id' => ['nullable', 'integer', Rule::exists('users', 'id')],
            'fecha_desde' => ['nullable', 'date_format:'.IncidenteRequest::FORMATOS_FECHA],
            'fecha_hasta' => ['nullable', 'date_format:'.IncidenteRequest::FORMATOS_FECHA, 'after_or_equal:fecha_desde'],
            'campo_fecha' => ['nullable', Rule::enum(CampoFecha::class)],
            'ordenar_por' => ['nullable', Rule::enum(CampoOrden::class)],
            'direccion' => ['nullable', Rule::enum(Direccion::class)],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'between:1,'.FiltrosDeIncidentes::POR_PAGINA_MAXIMO],
        ];
    }

    /**
     * Una búsqueda que se queda sin palabras útiles se rechaza con un mensaje, en lugar de devolver una lista vacía sin explicación.
     *
     * @return list<callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validador) {
                if ($this->filled('q') && $this->terminos() === []) {
                    $validador->errors()->add('q', trans('incidentes.busqueda_sin_terminos', ['minimo' => config('seguridad.busqueda.min_token')]));
                }
            },
        ];
    }

    public function filtros(): FiltrosDeIncidentes
    {
        return new FiltrosDeIncidentes(
            terminos: $this->terminos(),
            prioridad: $this->enum('prioridad', Prioridad::class),
            estado: $this->enum('estado', Estado::class),
            categoria: $this->enum('categoria', Categoria::class),
            usuarioAsignadoId: $this->filled('usuario_asignado_id') ? $this->integer('usuario_asignado_id') : null,
            fechaDesde: $this->fecha('fecha_desde'),
            fechaHasta: $this->fecha('fecha_hasta'),
            campoFecha: $this->enum('campo_fecha', CampoFecha::class) ?? CampoFecha::Vencimiento,
            ordenarPor: $this->enum('ordenar_por', CampoOrden::class) ?? CampoOrden::Id,
            direccion: $this->enum('direccion', Direccion::class) ?? Direccion::Descendente,
            porPagina: $this->filled('per_page') ? $this->integer('per_page') : FiltrosDeIncidentes::POR_PAGINA_PREDETERMINADO,
        );
    }

    /**
     * @return list<string>
     */
    private function terminos(): array
    {
        return TerminosDeBusqueda::desde(
            (string) $this->input('q'),
            (int) config('seguridad.busqueda.min_token'),
            (int) config('seguridad.busqueda.max_terminos'),
        );
    }

    // Los límites del rango se comparan en UTC, que es como se guardan las fechas.
    private function fecha(string $campo): ?Carbon
    {
        return $this->filled($campo) ? Carbon::parse($this->input($campo))->utc() : null;
    }
}
