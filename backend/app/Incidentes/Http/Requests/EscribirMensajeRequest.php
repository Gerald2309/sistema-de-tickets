<?php

namespace App\Incidentes\Http\Requests;

use App\Incidentes\Models\Incidente;
use App\Incidentes\Policies\IncidentePolicy;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\Rules\File;

class EscribirMensajeRequest extends FormRequest
{
    public const MAXIMO_CUERPO = 5000;

    public const MAXIMO_IMAGENES = 4;

    public const MAXIMO_KB_POR_IMAGEN = 5 * 1024;

    // Solo formatos de mapa de bits: un SVG puede llevar código. El tipo se comprueba por el contenido del
    // archivo, no por su extensión.
    public const FORMATOS = ['jpg', 'jpeg', 'png', 'webp'];

    // Escribir lo permite la ruta; marcar el mensaje como nota interna exige además poder escribirlas.
    public function authorize(): bool
    {
        /** @var Incidente $incidente */
        $incidente = $this->route('incidente');

        return ! $this->boolean('interno')
            || IncidentePolicy::alcanceDe($this->user())->puedeEscribirNotaInterna($incidente->participantes());
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            'cuerpo' => ['nullable', 'string', 'max:'.self::MAXIMO_CUERPO, 'required_without:imagenes'],
            'interno' => ['sometimes', 'boolean'],
            'imagenes' => ['sometimes', 'array', 'max:'.self::MAXIMO_IMAGENES],
            'imagenes.*' => [File::image()->types(self::FORMATOS)->max(self::MAXIMO_KB_POR_IMAGEN)],
        ];
    }

    public function cuerpo(): ?string
    {
        return $this->validated('cuerpo');
    }

    public function interno(): bool
    {
        return $this->boolean('interno');
    }

    /**
     * @return list<UploadedFile>
     */
    public function imagenes(): array
    {
        return array_values($this->validated('imagenes', []));
    }
}
