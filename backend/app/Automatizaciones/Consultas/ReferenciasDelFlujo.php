<?php

namespace App\Automatizaciones\Consultas;

use App\Automatizaciones\Dominio\Accion;
use App\Automatizaciones\Dominio\CampoDeCondicion;
use App\Automatizaciones\Dominio\Condicion;
use App\Automatizaciones\Dominio\Flujo;
use App\Automatizaciones\Dominio\TipoDeAccion;
use App\Identidad\Models\Cliente;
use App\Identidad\Models\Rol;
use App\Identidad\Models\User;

// Lo que el dominio no puede saber sin la base: que las personas, roles y empresas elegidos existan y sirvan
// para lo que se les pide. Una consulta por paso que nombra a alguien; un flujo tiene como máximo 40 pasos.
class ReferenciasDelFlujo
{
    /**
     * @return array<string, string> ruta del dato => qué está mal
     */
    public function errores(Flujo $flujo): array
    {
        $errores = [];

        foreach ($flujo->pasos() as $ruta => $paso) {
            $error = $paso instanceof Accion ? $this->deLaAccion($paso, $ruta) : $this->deLaCondicion($paso, $ruta);
            $errores += $error ?? [];
        }

        return $errores;
    }

    /**
     * @return array<string, string>|null
     */
    private function deLaAccion(Accion $accion, string $ruta): ?array
    {
        return match ($accion->tipo) {
            TipoDeAccion::Asignar => $this->atiende($accion->entero('usuario_id'))
                ? null
                : ["{$ruta}.usuario_id" => trans('automatizaciones.persona_no_atiende')],
            TipoDeAccion::Avisar => $this->existeDestinatario($accion)
                ? null
                : ["{$ruta}.id" => trans($accion->texto('destinatario') === 'rol' ? 'automatizaciones.rol_inexistente' : 'automatizaciones.persona_inactiva')],
            default => null,
        };
    }

    /**
     * @return array<string, string>|null
     */
    private function deLaCondicion(Condicion $condicion, string $ruta): ?array
    {
        if ($condicion->campo !== CampoDeCondicion::Empresa) {
            return null;
        }

        $existen = Cliente::query()->whereKey($condicion->valores)->count() === count($condicion->valores);

        return $existen ? null : ["{$ruta}.valores" => trans('automatizaciones.empresa_inexistente')];
    }

    private function atiende(int $usuarioId): bool
    {
        return User::query()->whereKey($usuarioId)->where('activo', true)->atiendenIncidentes()->exists();
    }

    private function existeDestinatario(Accion $accion): bool
    {
        $id = $accion->entero('id');

        return $accion->texto('destinatario') === 'rol'
            ? Rol::query()->whereKey($id)->exists()
            : User::query()->whereKey($id)->where('activo', true)->exists();
    }
}
