<?php

namespace Database\Seeders;

use App\Automatizaciones\Dominio\Disparador;
use App\Automatizaciones\Dominio\LectorDeFlujo;
use App\Automatizaciones\Dominio\PlazoDelSla;
use App\Automatizaciones\Models\Automatizacion;
use App\Identidad\Models\Rol;
use App\Identidad\Models\User;
use Illuminate\Database\Seeder;

// Tres automatizaciones de ejemplo, apagadas: muestran cómo se arma cada tipo sin actuar solas sobre la
// demostración. Se pueden sembrar aparte en una base que ya tiene datos (db:seed --class=...).
class AutomatizacionesDeEjemploSeeder extends Seeder
{
    public function run(): void
    {
        $administrador = User::where('email', config('seguridad.administrador.email'))->firstOrFail();
        $soporte = User::whereHas('rol', fn ($rol) => $rol->where('nombre', 'Soporte N2'))->where('activo', true)->orderBy('id')->firstOrFail();
        $administradores = Rol::where('nombre', 'Administrador')->firstOrFail();

        $ejemplos = [
            [
                'nombre' => 'Incidentes de red a Soporte N2',
                'disparador' => Disparador::Creacion,
                'pasos' => [[
                    'tipo' => 'condicion', 'campo' => 'categoria', 'valores' => ['red', 'telefonia'], 'si' => [
                        ['tipo' => 'accion', 'accion' => 'asignar', 'usuario_id' => $soporte->id],
                        ['tipo' => 'accion', 'accion' => 'responder', 'mensaje' => 'Hola {nombre}, recibimos tu caso {número}. Soporte N2 ya lo está revisando.'],
                    ],
                ]],
            ],
            [
                'nombre' => 'Críticos a punto de vencer',
                'disparador' => Disparador::SlaPorVencer,
                'porcentaje' => 80,
                'plazo' => PlazoDelSla::Respuesta,
                'pasos' => [[
                    'tipo' => 'condicion', 'campo' => 'prioridad', 'valores' => ['critica'], 'si' => [
                        ['tipo' => 'accion', 'accion' => 'avisar', 'destinatario' => 'rol', 'id' => $administradores->id],
                        ['tipo' => 'accion', 'accion' => 'nota_interna', 'texto' => 'Crítico al 80 % del plazo de respuesta: atenderlo ya.'],
                    ],
                ]],
            ],
            [
                'nombre' => 'Cerrar resueltos sin confirmar',
                'disparador' => Disparador::ResueltoSinConfirmar,
                'espera_minutos' => 3 * 24 * 60,
                'pasos' => [
                    ['tipo' => 'accion', 'accion' => 'cerrar', 'mensaje' => 'Cerramos tu caso {número} porque no recibimos confirmación en 3 días. Si el problema sigue, crea una nueva solicitud.'],
                ],
            ],
        ];

        foreach ($ejemplos as $ejemplo) {
            Automatizacion::firstOrCreate(['nombre' => $ejemplo['nombre']], [
                ...$ejemplo,
                'activa' => false,
                'autor_id' => $administrador->id,
                // Pasan por el lector del dominio: un ejemplo inválido falla aquí y no en la pantalla.
                'pasos' => LectorDeFlujo::leer($ejemplo['pasos'])->aArreglo(),
            ]);
        }
    }
}
