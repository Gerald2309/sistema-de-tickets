<?php

namespace App\Incidentes\Policies;

use App\Identidad\Models\User;
use App\Incidentes\Dominio\AlcanceIncidentes;
use App\Incidentes\Dominio\Participantes;
use App\Incidentes\Models\Incidente;
use App\Incidentes\Models\MensajeIncidente;
use Illuminate\Auth\Access\Response;

// Traduce la regla de AlcanceIncidentes a respuestas: lo que el usuario no puede ver responde 404
// (no revela que existe) y lo que ve pero no puede hacer responde 403.
class IncidentePolicy
{
    public static function alcanceDe(User $usuario): AlcanceIncidentes
    {
        return new AlcanceIncidentes($usuario->permisos(), $usuario->id);
    }

    public function listar(User $usuario): Response
    {
        return $this->permitirSi(self::alcanceDe($usuario)->puedeListar());
    }

    public function actualizarEnLote(User $usuario): Response
    {
        return $this->permitirSi(self::alcanceDe($usuario)->puedeActualizarEnLote());
    }

    public function eliminarEnLote(User $usuario): Response
    {
        return $this->permitirSi(self::alcanceDe($usuario)->puedeBorrarEnLote());
    }

    public function verDashboard(User $usuario): Response
    {
        return $this->permitirSi(self::alcanceDe($usuario)->puedeVerDashboard());
    }

    public function planearSuDia(User $usuario): Response
    {
        return $this->permitirSi(self::alcanceDe($usuario)->puedePlanearSuDia());
    }

    // La marca de «bloqueado» es personal, pero solo sobre un incidente que la persona ve.
    public function marcarBloqueo(User $usuario, Incidente $incidente): Response
    {
        return $this->sobre($usuario, $incidente, fn (AlcanceIncidentes $alcance) => $alcance->puedePlanearSuDia());
    }

    public function verAvisos(User $usuario): Response
    {
        return $this->permitirSi(self::alcanceDe($usuario)->puedeVerAvisos());
    }

    public function crear(User $usuario): Response
    {
        return $this->permitirSi(self::alcanceDe($usuario)->puedeCrear());
    }

    public function ver(User $usuario, Incidente $incidente): Response
    {
        return $this->sobre($usuario, $incidente, fn () => true);
    }

    public function tomar(User $usuario, Incidente $incidente): Response
    {
        return $this->sobre($usuario, $incidente, fn (AlcanceIncidentes $alcance, Participantes $participantes) => $alcance->puedeTomar($participantes));
    }

    public function actualizar(User $usuario, Incidente $incidente): Response
    {
        return $this->sobre($usuario, $incidente, fn (AlcanceIncidentes $alcance, Participantes $participantes) => $alcance->puedeActualizar($participantes));
    }

    public function confirmarSolucion(User $usuario, Incidente $incidente): Response
    {
        return $this->sobre($usuario, $incidente, fn (AlcanceIncidentes $alcance, Participantes $participantes) => $alcance->puedeConfirmarSolucion($participantes));
    }

    public function eliminar(User $usuario, Incidente $incidente): Response
    {
        return $this->sobre($usuario, $incidente, fn (AlcanceIncidentes $alcance, Participantes $participantes) => $alcance->puedeBorrar($participantes));
    }

    public function verHistorial(User $usuario, Incidente $incidente): Response
    {
        return $this->sobre($usuario, $incidente, fn (AlcanceIncidentes $alcance, Participantes $participantes) => $alcance->puedeVerHistorial($participantes));
    }

    public function verSimilares(User $usuario, Incidente $incidente): Response
    {
        return $this->sobre($usuario, $incidente, fn (AlcanceIncidentes $alcance, Participantes $participantes) => $alcance->puedeVerSimilares($participantes));
    }

    public function escribirMensaje(User $usuario, Incidente $incidente): Response
    {
        return $this->sobre($usuario, $incidente, fn (AlcanceIncidentes $alcance, Participantes $participantes) => $alcance->puedeConversar($participantes));
    }

    // Una imagen de una nota interna no existe para quien no puede leer la nota.
    public function verAdjunto(User $usuario, Incidente $incidente, MensajeIncidente $mensaje): Response
    {
        $alcance = self::alcanceDe($usuario);

        return $alcance->puedeVer($incidente->participantes()) && (! $mensaje->interno || $alcance->puedeLeerNotasInternas())
            ? Response::allow()
            : Response::denyAsNotFound();
    }

    /**
     * @param  callable(AlcanceIncidentes, Participantes): bool  $accion
     */
    private function sobre(User $usuario, Incidente $incidente, callable $accion): Response
    {
        $alcance = self::alcanceDe($usuario);
        $participantes = $incidente->participantes();

        if (! $alcance->puedeVer($participantes)) {
            return Response::denyAsNotFound();
        }

        return $this->permitirSi($accion($alcance, $participantes));
    }

    private function permitirSi(bool $permitido): Response
    {
        return $permitido ? Response::allow() : Response::deny();
    }
}
