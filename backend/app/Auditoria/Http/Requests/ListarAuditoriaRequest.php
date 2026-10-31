<?php

namespace App\Auditoria\Http\Requests;

use App\Auditoria\Consultas\ListarAuditoria;
use App\Auditoria\Dominio\AccionAuditoria;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;

class ListarAuditoriaRequest extends FormRequest
{
    private const FORMATOS_FECHA = 'Y-m-d\TH:i:sP,Y-m-d\TH:i:s\Z';

    // Los permisos los comprueba la ruta (->can('ver_auditoria')).
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
            'accion' => ['nullable', Rule::enum(AccionAuditoria::class)],
            'usuario_id' => ['nullable', 'integer', 'min:1'],
            'usuario_afectado_id' => ['nullable', 'integer', 'min:1'],
            'desde' => ['nullable', 'date_format:'.self::FORMATOS_FECHA],
            'hasta' => ['nullable', 'date_format:'.self::FORMATOS_FECHA, 'after_or_equal:desde'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'between:1,'.ListarAuditoria::POR_PAGINA_MAXIMO],
        ];
    }

    /**
     * @return array{accion: AccionAuditoria|null, usuario_id: int|null, usuario_afectado_id: int|null, desde: Carbon|null, hasta: Carbon|null}
     */
    public function filtros(): array
    {
        return [
            'accion' => $this->enum('accion', AccionAuditoria::class),
            'usuario_id' => $this->filled('usuario_id') ? $this->integer('usuario_id') : null,
            'usuario_afectado_id' => $this->filled('usuario_afectado_id') ? $this->integer('usuario_afectado_id') : null,
            'desde' => $this->filled('desde') ? Carbon::parse($this->string('desde')->toString()) : null,
            'hasta' => $this->filled('hasta') ? Carbon::parse($this->string('hasta')->toString()) : null,
        ];
    }

    public function porPagina(): int
    {
        return $this->integer('per_page', ListarAuditoria::POR_PAGINA_PREDETERMINADO);
    }
}
