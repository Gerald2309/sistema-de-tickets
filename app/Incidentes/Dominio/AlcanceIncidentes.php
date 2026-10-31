<?php

namespace App\Incidentes\Dominio;

use App\Identidad\Dominio\ClavePermiso;
use App\Identidad\Dominio\ConjuntoPermisos;

// Quién ve y qué puede hacer con cada incidente (RF-25, D-18). Es la única definición de la regla:
// la Policy y los permisos por campo de las peticiones la consultan.
//
// Toda acción sobre un incidente exige poder verlo. Quien no tiene `ver_todos` solo ve los que creó.
final class AlcanceIncidentes
{
    private const CAMPOS_DE_EDICION = ['titulo', 'descripcion', 'prioridad', 'categoria', 'fecha_vencimiento'];

    private const CAMPOS_DE_ESTADO = ['estado', 'solucion'];

    private const CAMPOS_DE_ASIGNACION = ['usuario_asignado_id'];

    public function __construct(
        private readonly ConjuntoPermisos $permisos,
        private readonly int $usuarioId,
    ) {}

    public function puedeCrear(): bool
    {
        return $this->permisos->tiene(ClavePermiso::CrearIncidente);
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

    public function puedeVer(int $creadorId): bool
    {
        return $this->veTodo() || ($this->permisos->tiene(ClavePermiso::VerPropios) && $this->esPropio($creadorId));
    }

    public function puedeEditar(int $creadorId): bool
    {
        return $this->puedeVer($creadorId) && (
            $this->permisos->tiene(ClavePermiso::EditarTodos)
            || ($this->permisos->tiene(ClavePermiso::EditarPropios) && $this->esPropio($creadorId))
        );
    }

    public function puedeCambiarEstado(int $creadorId): bool
    {
        return $this->puedeVer($creadorId) && $this->permisos->tiene(ClavePermiso::CambiarEstado);
    }

    public function puedeReasignar(int $creadorId): bool
    {
        return $this->puedeVer($creadorId) && $this->permisos->tiene(ClavePermiso::Reasignar);
    }

    public function puedeBorrar(int $creadorId): bool
    {
        return $this->puedeVer($creadorId) && $this->permisos->tiene(ClavePermiso::Borrar);
    }

    public function puedeVerHistorial(int $creadorId): bool
    {
        return $this->puedeVer($creadorId) && $this->permisos->tiene(ClavePermiso::VerHistorial);
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

    // El aviso resume incidentes de todas las personas: solo para quien ve todo.
    public function puedeVerAvisos(): bool
    {
        return $this->veTodo();
    }

    // Los casos parecidos muestran la solución de incidentes de otras personas: solo para quien ve todo.
    public function puedeVerSimilares(int $creadorId): bool
    {
        return $this->veTodo() && $this->puedeVer($creadorId);
    }

    // Para intentar una modificación basta con poder hacer alguna de sus partes.
    public function puedeActualizar(int $creadorId): bool
    {
        return $this->puedeEditar($creadorId)
            || $this->puedeCambiarEstado($creadorId)
            || $this->puedeReasignar($creadorId);
    }

    /**
     * Permiso por campo: cada campo presente exige la capacidad que le corresponde.
     * Los campos desconocidos no se evalúan: la validación los descarta.
     *
     * @param  list<string>  $campos
     */
    public function puedeModificar(array $campos, int $creadorId): bool
    {
        $grupos = [
            [self::CAMPOS_DE_EDICION, $this->puedeEditar($creadorId)],
            [self::CAMPOS_DE_ESTADO, $this->puedeCambiarEstado($creadorId)],
            [self::CAMPOS_DE_ASIGNACION, $this->puedeReasignar($creadorId)],
        ];

        foreach ($grupos as [$camposDelGrupo, $permitido]) {
            if (array_intersect($campos, $camposDelGrupo) !== [] && ! $permitido) {
                return false;
            }
        }

        return true;
    }

    private function esPropio(int $creadorId): bool
    {
        return $creadorId === $this->usuarioId;
    }
}
