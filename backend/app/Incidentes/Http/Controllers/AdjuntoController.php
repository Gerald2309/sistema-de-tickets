<?php

namespace App\Incidentes\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Incidentes\Acciones\EscribirMensaje;
use App\Incidentes\Models\AdjuntoMensaje;
use App\Incidentes\Models\Incidente;
use App\Incidentes\Models\MensajeIncidente;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AdjuntoController extends Controller
{
    // El tipo es el que se comprobó al recibir la imagen; se entrega para mostrarla, no para descargarla.
    /**
     * Descargar una imagen adjunta
     */
    public function __invoke(Incidente $incidente, MensajeIncidente $mensaje, AdjuntoMensaje $adjunto): StreamedResponse
    {
        return Storage::disk(EscribirMensaje::DISCO)->response($adjunto->ruta, $adjunto->nombre, ['Content-Type' => $adjunto->tipo]);
    }
}
