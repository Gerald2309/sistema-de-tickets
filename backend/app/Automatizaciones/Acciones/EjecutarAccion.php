<?php

namespace App\Automatizaciones\Acciones;

use App\Automatizaciones\Dominio\Accion;
use App\Automatizaciones\Dominio\Excepciones\AccionNoAplicable;
use App\Automatizaciones\Dominio\PlantillaDeMensaje;
use App\Automatizaciones\Dominio\TipoDeAccion;
use App\Automatizaciones\Models\Automatizacion;
use App\Identidad\Models\User;
use App\Incidentes\Acciones\ActualizarIncidente;
use App\Incidentes\Acciones\EscribirMensaje;
use App\Incidentes\Dominio\CodigoIncidente;
use App\Incidentes\Dominio\Estado;
use App\Incidentes\Models\Incidente;
use App\Incidentes\Notificaciones\AvisoDeIncidente;
use DomainException;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;

// Hace una acción de una automatización con las mismas piezas que usa una persona: queda en el historial y
// en la conversación a nombre de quien guardó la automatización, con el nombre de esta como origen.
class EjecutarAccion
{
    public function __construct(
        private readonly ActualizarIncidente $actualizarIncidente,
        private readonly EscribirMensaje $escribirMensaje,
    ) {}

    /**
     * @throws DomainException|ValidationException si en este incidente no se puede hacer
     */
    public function ejecutar(Accion $accion, Incidente $incidente, Automatizacion $automatizacion): void
    {
        match ($accion->tipo) {
            TipoDeAccion::Asignar => $this->actualizar($incidente, $automatizacion, [
                'usuario_asignado_id' => $this->quienAtiende($accion->entero('usuario_id')),
            ]),
            TipoDeAccion::CambiarPrioridad => $this->actualizar($incidente, $automatizacion, ['prioridad' => $accion->texto('prioridad')]),
            TipoDeAccion::CambiarCategoria => $this->actualizar($incidente, $automatizacion, ['categoria' => $accion->texto('categoria')]),
            TipoDeAccion::Responder => $this->escribir($incidente, $automatizacion, $accion->texto('mensaje'), interno: false),
            TipoDeAccion::NotaInterna => $this->escribir($incidente, $automatizacion, $accion->texto('texto'), interno: true),
            TipoDeAccion::Cerrar => $this->cerrar($incidente, $automatizacion, $accion->texto('mensaje')),
            TipoDeAccion::Avisar => $this->avisar($incidente, $automatizacion, $accion),
        };
    }

    /**
     * @param  array<string, mixed>  $datos
     */
    private function actualizar(Incidente $incidente, Automatizacion $automatizacion, array $datos): void
    {
        $this->actualizarIncidente->ejecutar($automatizacion->autor, $incidente, $datos, $automatizacion->nombre);
    }

    private function escribir(Incidente $incidente, Automatizacion $automatizacion, string $plantilla, bool $interno): void
    {
        $cuerpo = $this->rellenar($plantilla, $incidente);

        $this->escribirMensaje->ejecutar($automatizacion->autor, $incidente, $cuerpo, $interno, [], origen: $automatizacion->nombre);
    }

    // Se revisa antes de escribir: si no se puede cerrar, tampoco se manda el mensaje de cierre.
    private function cerrar(Incidente $incidente, Automatizacion $automatizacion, string $plantilla): void
    {
        $incidente->estado->pasarA(Estado::Cerrado);

        $this->escribir($incidente, $automatizacion, $plantilla, interno: false);
        $this->actualizar($incidente, $automatizacion, ['estado' => Estado::Cerrado->value]);
    }

    // A una persona o a todas las personas activas de un rol (si el rol no tiene ninguna, no hay a quién avisar).
    private function avisar(Incidente $incidente, Automatizacion $automatizacion, Accion $accion): void
    {
        $id = $accion->entero('id');
        $aUnaPersona = $accion->texto('destinatario') === 'usuario';
        $personas = User::query()
            ->where('activo', true)
            ->when($aUnaPersona, fn ($consulta) => $consulta->whereKey($id), fn ($consulta) => $consulta->where('rol_id', $id))
            ->get();

        if ($aUnaPersona && $personas->isEmpty()) {
            throw new AccionNoAplicable('La persona a la que avisa ya no está activa.');
        }

        $texto = sprintf('«%s»: %s · %s', $automatizacion->nombre, CodigoIncidente::deId($incidente->id), $incidente->titulo);

        Notification::send($personas, new AvisoDeIncidente($incidente->id, $texto));
    }

    private function quienAtiende(int $usuarioId): int
    {
        $puede = User::query()->whereKey($usuarioId)->where('activo', true)->atiendenIncidentes()->exists();

        return $puede ? $usuarioId : throw new AccionNoAplicable('La persona a la que asigna ya no puede atender incidentes.');
    }

    private function rellenar(string $plantilla, Incidente $incidente): string
    {
        return PlantillaDeMensaje::rellenar($plantilla, [
            'nombre' => $incidente->creador->name,
            'número' => CodigoIncidente::deId($incidente->id),
            'título' => $incidente->titulo,
            'empresa' => $incidente->cliente->nombre ?? '',
            'prioridad' => $incidente->prioridad->nombre(),
        ]);
    }
}
