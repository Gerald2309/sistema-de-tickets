<?php

namespace App\Incidentes\Dominio;

use App\Identidad\Dominio\ClavePermiso;
use App\Identidad\Dominio\ConjuntoPermisos;

// Quién ve y qué puede hacer con cada incidente (RF-25, D-18). Es la única definición de la regla:
// la Policy y los permisos por campo de las peticiones la consultan.
//
// Toda acción sobre un incidente exige poder verlo. Quien no tiene `ver_todos` solo ve los que creó.
// Un incidente lo trabaja solo quien lo tiene asignado: primero se toma y después se modifica. El
// solicitante corrige la clasificación del suyo mientras nadie lo ha tomado, salvo lo que mueve los plazos del
// acuerdo de servicio: marcarlo como crítico o cambiar su vencimiento es de soporte. El título y la descripción
// son las palabras de quien lo reportó: solo esa persona los corrige, y solo mientras nadie lo ha tomado; después
// quedan fijos y lo que falte se pide en la conversación.
final class AlcanceIncidentes
{
    private const CAMPOS_DEL_RELATO = ['titulo', 'descripcion'];

    private const CAMPOS_DE_CLASIFICACION = ['prioridad', 'categoria', 'fecha_vencimiento'];

    private const CAMPOS_DE_ESTADO = ['estado', 'solucion'];

    private const CAMPO_DE_ASIGNACION = 'usuario_asignado_id';

    public function __construct(
        private readonly ConjuntoPermisos $permisos,
        private readonly int $usuarioId,
    ) {}

    public function puedeCrear(): bool
    {
        return $this->permisos->tiene(ClavePermiso::CrearIncidente);
    }

    // Elegir a quién se asigna un incidente nuevo es repartir trabajo: solo quien reasigna cualquiera.
    public function puedeAsignarAlCrear(): bool
    {
        return $this->permisos->tiene(ClavePermiso::Reasignar);
    }

    /**
     * Marcar un incidente como crítico o cambiar su vencimiento mueve los plazos acordados: solo soporte.
     *
     * @param  array<string, mixed>  $cambios  campo => valor pedido
     */
    public function puedePedirEstosPlazos(array $cambios): bool
    {
        $muevePlazos = array_key_exists('fecha_vencimiento', $cambios)
            || ($cambios['prioridad'] ?? null) === Prioridad::Critica->value;

        return ! $muevePlazos || $this->permisos->tiene(ClavePermiso::EditarTodos);
    }

    public function puedeListar(): bool
    {
        return $this->permisos->alguno(ClavePermiso::VerTodos, ClavePermiso::VerPropios);
    }

    public function veTodo(): bool
    {
        return $this->permisos->tiene(ClavePermiso::VerTodos);
    }

    // Ve únicamente lo que creó: tiene `ver_propios` y no `ver_todos`.
    public function veSoloLoPropio(): bool
    {
        return ! $this->veTodo() && $this->permisos->tiene(ClavePermiso::VerPropios);
    }

    public function usuarioId(): int
    {
        return $this->usuarioId;
    }

    public function puedeVer(Participantes $participantes): bool
    {
        return $this->veTodo() || ($this->permisos->tiene(ClavePermiso::VerPropios) && $this->loCreo($participantes));
    }

    // Quien atiende incidentes toma uno libre para trabajarlo. Si sigue libre lo decide la acción, con el
    // incidente bloqueado: dos personas pueden intentarlo a la vez.
    public function puedeTomar(Participantes $participantes): bool
    {
        return $this->puedeVer($participantes) && $this->permisos->tiene(ClavePermiso::CambiarEstado);
    }

    // Prioridad, categoría y vencimiento: quien lo atiende, o el solicitante mientras nadie lo ha tomado.
    public function puedeClasificar(Participantes $participantes): bool
    {
        if (! $this->puedeVer($participantes)) {
            return false;
        }

        return ($this->permisos->tiene(ClavePermiso::EditarTodos) && $this->loAtiende($participantes))
            || ($this->permisos->tiene(ClavePermiso::EditarPropios) && $this->loCreo($participantes) && $participantes->sinAsignar());
    }

    // El título y la descripción: quien lo reportó, mientras nadie lo ha tomado. Después, lo que atiende soporte
    // es ese relato y no cambia por debajo.
    public function puedeCorregirRelato(Participantes $participantes): bool
    {
        return $this->puedeVer($participantes)
            && $this->permisos->tiene(ClavePermiso::EditarPropios)
            && $this->loCreo($participantes)
            && $participantes->sinAsignar();
    }

    public function puedeCambiarEstado(Participantes $participantes): bool
    {
        return $this->puedeVer($participantes)
            && $this->permisos->tiene(ClavePermiso::CambiarEstado)
            && $this->loAtiende($participantes);
    }

    // Quien lo atiende puede pasarlo a otra persona (por ejemplo, escalarlo); quien tiene `reasignar`
    // mueve cualquiera.
    public function puedeReasignar(Participantes $participantes): bool
    {
        return $this->puedeVer($participantes)
            && ($this->permisos->tiene(ClavePermiso::Reasignar) || $this->loAtiende($participantes));
    }

    // Dejarlo sin nadie que lo atienda es devolverlo a la cola: solo quien reparte el trabajo.
    public function puedeDejarSinAsignar(Participantes $participantes): bool
    {
        return $this->puedeVer($participantes) && $this->permisos->tiene(ClavePermiso::Reasignar);
    }

    // Si la solución sirvió lo dice quien reportó el incidente: cierra el caso o lo devuelve a soporte.
    public function puedeConfirmarSolucion(Participantes $participantes): bool
    {
        return $this->puedeVer($participantes) && $this->loCreo($participantes);
    }

    public function puedeBorrar(Participantes $participantes): bool
    {
        return $this->puedeVer($participantes) && $this->permisos->tiene(ClavePermiso::Borrar);
    }

    public function puedeVerHistorial(Participantes $participantes): bool
    {
        return $this->puedeVer($participantes) && $this->permisos->tiene(ClavePermiso::VerHistorial);
    }

    // En lote hay que poder hacer algo sobre lo que se ve; cada incidente se comprueba después, uno a uno.
    public function puedeActualizarEnLote(): bool
    {
        return $this->puedeListar() && $this->permisos->alguno(
            ClavePermiso::EditarPropios,
            ClavePermiso::EditarTodos,
            ClavePermiso::CambiarEstado,
            ClavePermiso::Reasignar,
        );
    }

    public function puedeBorrarEnLote(): bool
    {
        return $this->puedeListar() && $this->permisos->tiene(ClavePermiso::Borrar);
    }

    // El dashboard cuenta solo lo que la persona ve; el permiso decide si accede a él.
    public function puedeVerDashboard(): bool
    {
        return $this->permisos->tiene(ClavePermiso::VerDashboard);
    }

    // «Mi día» es para quien atiende incidentes: organiza su propio trabajo.
    public function puedePlanearSuDia(): bool
    {
        return $this->permisos->tiene(ClavePermiso::CambiarEstado);
    }

    // El aviso resume incidentes de todas las personas: solo para quien ve todo.
    public function puedeVerAvisos(): bool
    {
        return $this->veTodo();
    }

    // Los casos parecidos muestran la solución de incidentes de otras personas: solo para quien ve todo.
    public function puedeVerSimilares(Participantes $participantes): bool
    {
        return $this->veTodo() && $this->puedeVer($participantes);
    }

    // La conversación es entre quien reportó el incidente y quien lo atiende; el resto de soporte la lee.
    public function puedeConversar(Participantes $participantes): bool
    {
        return $this->puedeVer($participantes) && ($this->loCreo($participantes) || $this->loAtiende($participantes));
    }

    // Las notas internas son de soporte: las escribe quien atiende y nunca las lee el solicitante.
    public function puedeEscribirNotaInterna(Participantes $participantes): bool
    {
        return $this->veTodo() && $this->loAtiende($participantes);
    }

    public function puedeLeerNotasInternas(): bool
    {
        return $this->veTodo();
    }

    // Para intentar una modificación basta con poder hacer alguna de sus partes.
    public function puedeActualizar(Participantes $participantes): bool
    {
        return $this->puedeClasificar($participantes)
            || $this->puedeCorregirRelato($participantes)
            || $this->puedeCambiarEstado($participantes)
            || $this->puedeReasignar($participantes);
    }

    /**
     * Permiso por campo: cada campo presente exige la capacidad que le corresponde, y quitar la
     * asignación exige además poder dejarlo sin asignar. Los campos desconocidos no se evalúan: la
     * validación los descarta.
     *
     * @param  array<string, mixed>  $cambios  campo => valor pedido
     */
    public function puedeModificar(array $cambios, Participantes $participantes): bool
    {
        if (! $this->puedePedirEstosPlazos($cambios)) {
            return false;
        }

        $campos = array_keys($cambios);

        $grupos = [
            [self::CAMPOS_DEL_RELATO, $this->puedeCorregirRelato($participantes)],
            [self::CAMPOS_DE_CLASIFICACION, $this->puedeClasificar($participantes)],
            [self::CAMPOS_DE_ESTADO, $this->puedeCambiarEstado($participantes)],
            [[self::CAMPO_DE_ASIGNACION], $this->puedeReasignar($participantes)],
        ];

        foreach ($grupos as [$camposDelGrupo, $permitido]) {
            if (array_intersect($campos, $camposDelGrupo) !== [] && ! $permitido) {
                return false;
            }
        }

        $quitaLaAsignacion = array_key_exists(self::CAMPO_DE_ASIGNACION, $cambios) && $cambios[self::CAMPO_DE_ASIGNACION] === null;

        return ! $quitaLaAsignacion || $this->puedeDejarSinAsignar($participantes);
    }

    private function loCreo(Participantes $participantes): bool
    {
        return $participantes->creadorId === $this->usuarioId;
    }

    private function loAtiende(Participantes $participantes): bool
    {
        return $participantes->asignadoId === $this->usuarioId;
    }
}
