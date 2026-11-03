<?php

namespace Tests\Unit\Automatizaciones;

// Pasos con la misma forma que envía el editor de automatizaciones.
trait PasosDeEjemplo
{
    /**
     * @param  list<int|string>  $valores
     * @param  list<array<string, mixed>>  $si
     * @param  list<array<string, mixed>>  $no
     * @return array<string, mixed>
     */
    private static function condicion(string $campo, array $valores, array $si = [], array $no = [], bool $niega = false): array
    {
        return ['tipo' => 'condicion', 'campo' => $campo, 'valores' => $valores, 'niega' => $niega, 'si' => $si, 'no' => $no];
    }

    /**
     * @return array<string, mixed>
     */
    private static function asignar(int $usuarioId): array
    {
        return ['tipo' => 'accion', 'accion' => 'asignar', 'usuario_id' => $usuarioId];
    }

    /**
     * @return array<string, mixed>
     */
    private static function responder(string $mensaje): array
    {
        return ['tipo' => 'accion', 'accion' => 'responder', 'mensaje' => $mensaje];
    }

    /**
     * @return array<string, mixed>
     */
    private static function avisarAlRol(int $rolId): array
    {
        return ['tipo' => 'accion', 'accion' => 'avisar', 'destinatario' => 'rol', 'id' => $rolId];
    }
}
