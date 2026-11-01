<?php

namespace App\Incidentes\Acciones;

use App\Identidad\Models\User;
use App\Incidentes\Dominio\Excepciones\ConversacionCerrada;
use App\Incidentes\Dominio\TipoDeCambio;
use App\Incidentes\Models\Incidente;
use App\Incidentes\Models\MensajeIncidente;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;

class EscribirMensaje
{
    // Disco privado: sus archivos no se publican, solo los entrega la API tras autorizar.
    public const DISCO = 'local';

    public function __construct(private readonly AvisarCambios $avisarCambios) {}

    /**
     * Guarda el mensaje y sus imágenes, todo o nada: si algo falla, no queda el mensaje ni ningún archivo.
     *
     * @param  list<UploadedFile>  $imagenes  ya validadas
     *
     * @throws ConversacionCerrada si el incidente está cerrado.
     */
    public function ejecutar(User $autor, Incidente $incidente, ?string $cuerpo, bool $interno, array $imagenes): MensajeIncidente
    {
        if ($incidente->estado->esFinal()) {
            throw new ConversacionCerrada;
        }

        $guardadas = [];

        try {
            return DB::transaction(function () use ($autor, $incidente, $cuerpo, $interno, $imagenes, &$guardadas) {
                $mensaje = $incidente->mensajes()->create(['usuario_id' => $autor->id, 'cuerpo' => $cuerpo, 'interno' => $interno]);
                $this->anotarPrimeraRespuesta($autor, $incidente, $interno);

                foreach ($imagenes as $imagen) {
                    $ruta = $imagen->store("incidentes/{$incidente->id}", self::DISCO) ?: throw new RuntimeException('No se pudo guardar la imagen.');
                    $guardadas[] = $ruta;

                    $mensaje->adjuntos()->create([
                        'ruta' => $ruta,
                        'nombre' => mb_substr(basename($imagen->getClientOriginalName()), 0, 255),
                        'tipo' => $imagen->getMimeType(),
                        'bytes' => $imagen->getSize(),
                    ]);
                }

                $this->avisarCambios->registrar($incidente, TipoDeCambio::Actualizado);

                return $mensaje->load(['autor:id,name', 'adjuntos']);
            });
        } catch (Throwable $error) {
            Storage::disk(self::DISCO)->delete($guardadas);

            throw $error;
        }
    }

    // Responde quien le escribe al solicitante: ni sus propios mensajes ni las notas internas cuentan.
    private function anotarPrimeraRespuesta(User $autor, Incidente $incidente, bool $interno): void
    {
        if (! $interno && $autor->id !== $incidente->usuario_creador_id && $incidente->primera_respuesta_en === null) {
            $incidente->primera_respuesta_en = Date::now();
            $incidente->save();
        }
    }
}
