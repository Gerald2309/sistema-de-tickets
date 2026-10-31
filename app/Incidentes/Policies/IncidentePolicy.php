<?php

namespace App\Incidentes\Policies;

use App\Identidad\Models\User;
use App\Incidentes\Dominio\AlcanceIncidentes;
use App\Incidentes\Models\Incidente;
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

    public function crear(User $usuario): Response
    {
        return $this->permitirSi(self::alcanceDe($usuario)->puedeCrear());
    }

    public function ver(User $usuario, Incidente $incidente): Response
    {
        return $this->sobre($usuario, $incidente, fn () => true);
    }

    public function actualizar(User $usuario, Incidente $incidente): Response
    {
        return $this->sobre($usuario, $incidente, fn (AlcanceIncidentes $alcance, int $creador) => $alcance->puedeActualizar($creador));
    }

    public function eliminar(User $usuario, Incidente $incidente): Response
    {
        return $this->sobre($usuario, $incidente, fn (AlcanceIncidentes $alcance, int $creador) => $alcance->puedeBorrar($creador));
    }

    public function verHistorial(User $usuario, Incidente $incidente): Response
    {
        return $this->sobre($usuario, $incidente, fn (AlcanceIncidentes $alcance, int $creador) => $alcance->puedeVerHistorial($creador));
    }

    public function verSimilares(User $usuario, Incidente $incidente): Response
    {
        return $this->sobre($usuario, $incidente, fn (AlcanceIncidentes $alcance, int $creador) => $alcance->puedeVerSimilares($creador));
    }

    /**
     * @param  callable(AlcanceIncidentes, int): bool  $accion
     */
    private function sobre(User $usuario, Incidente $incidente, callable $accion): Response
    {
        $alcance = self::alcanceDe($usuario);
        $creador = $incidente->usuario_creador_id;

        if (! $alcance->puedeVer($creador)) {
            return Response::denyAsNotFound();
        }

        return $this->permitirSi($accion($alcance, $creador));
    }

    private function permitirSi(bool $permitido): Response
    {
        return $permitido ? Response::allow() : Response::deny();
    }
}
