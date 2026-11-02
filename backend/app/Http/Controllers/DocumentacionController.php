<?php

namespace App\Http\Controllers;

use Dedoc\Scramble\Generator;
use Dedoc\Scramble\Scramble;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;

/**
 * La documentación de la API es pública, y generarla tarda segundos: se sirve del archivo que exporta
 * `app:preparar` al arrancar. Si no está (por ejemplo, en las pruebas), se genera una vez y se guarda.
 *
 * No se usa la caché de Scramble: guarda objetos, y la caché de Laravel no reconstruye clases que no estén
 * permitidas (`serializable_classes`), una protección que conviene mantener.
 */
class DocumentacionController extends Controller
{
    public const ARCHIVO = 'openapi.json';

    public function contrato(Generator $generador): JsonResponse
    {
        return response()->json($this->documento($generador));
    }

    public function visor(Generator $generador): View
    {
        return view('scramble::docs', [
            'spec' => $this->documento($generador),
            'config' => Scramble::getGeneratorConfig(Scramble::DEFAULT_API),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function documento(Generator $generador): array
    {
        $disco = Storage::disk('local');

        if (! $disco->exists(self::ARCHIVO)) {
            $disco->put(self::ARCHIVO, (string) json_encode($generador(), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
        }

        return (array) json_decode((string) $disco->get(self::ARCHIVO), true);
    }
}
