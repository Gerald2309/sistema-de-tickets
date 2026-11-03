<?php

namespace Tests\Unit\Automatizaciones;

use App\Automatizaciones\Dominio\Excepciones\FlujoInvalido;
use App\Automatizaciones\Dominio\LectorDeFlujo;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class LectorDeFlujoTest extends TestCase
{
    use PasosDeEjemplo;

    public function test_lo_leido_se_guarda_tal_cual(): void
    {
        $pasos = [
            self::avisarAlRol(2),
            ['tipo' => 'accion', 'accion' => 'cambiar_prioridad', 'prioridad' => 'alta'],
            self::condicion('categoria', ['red'],
                si: [['tipo' => 'accion', 'accion' => 'nota_interna', 'texto' => 'Revisar la VPN de {empresa}']],
                no: [['tipo' => 'accion', 'accion' => 'cerrar', 'mensaje' => 'Lo cerramos.']],
                operador: 'no_es_ninguna_de',
            ),
        ];

        $this->assertSame($pasos, LectorDeFlujo::leer($pasos)->aArreglo());
    }

    public function test_sin_operador_se_entiende_es_una_de(): void
    {
        $sinOperador = ['tipo' => 'condicion', 'campo' => 'prioridad', 'valores' => ['alta']];

        $this->assertSame([self::condicion('prioridad', ['alta'])], LectorDeFlujo::leer([$sinOperador])->aArreglo());
    }

    public function test_admite_tres_condiciones_seguidas(): void
    {
        $pasos = [self::condicion('prioridad', ['alta'], si: [
            self::condicion('categoria', ['red'], no: [
                self::condicion('empresa', [1], si: [self::asignar(7)]),
            ]),
        ])];

        $this->assertSame($pasos, LectorDeFlujo::leer($pasos)->aArreglo());
    }

    /**
     * @return array<string, array{mixed, string}>
     */
    public static function invalidos(): array
    {
        $cuatroSeguidas = self::condicion('prioridad', ['alta'], si: [
            self::condicion('prioridad', ['alta'], si: [
                self::condicion('prioridad', ['alta'], si: [
                    self::condicion('prioridad', ['alta']),
                ]),
            ]),
        ]);

        return [
            'no es una lista' => [['a' => self::asignar(7)], 'pasos'],
            'un paso que no es un objeto' => [[5], 'pasos.0'],
            'un tipo de paso desconocido' => [[['tipo' => 'esperar']], 'pasos.0'],
            'una acción desconocida' => [[['tipo' => 'accion', 'accion' => 'borrar']], 'pasos.0.accion'],
            'asignar sin persona' => [[['tipo' => 'accion', 'accion' => 'asignar']], 'pasos.0.usuario_id'],
            'asignar con un id de texto' => [[['tipo' => 'accion', 'accion' => 'asignar', 'usuario_id' => '7']], 'pasos.0.usuario_id'],
            'responder en blanco' => [[self::responder('   ')], 'pasos.0.mensaje'],
            'avisar a un grupo' => [[['tipo' => 'accion', 'accion' => 'avisar', 'destinatario' => 'grupo', 'id' => 2]], 'pasos.0.destinatario'],
            'una prioridad que no existe' => [[['tipo' => 'accion', 'accion' => 'cambiar_prioridad', 'prioridad' => 'urgente']], 'pasos.0.prioridad'],
            'un paso después de la condición' => [[self::condicion('prioridad', ['alta']), self::asignar(7)], 'pasos.1'],
            'una condición desconocida' => [[self::condicion('cliente', [1])], 'pasos.0.campo'],
            'sin opciones' => [[self::condicion('prioridad', [])], 'pasos.0.valores'],
            'una opción que no es de ese campo' => [[self::condicion('categoria', ['red', 'alta'])], 'pasos.0.valores.1'],
            'una empresa con id de texto' => [[self::condicion('empresa', ['5'])], 'pasos.0.valores.0'],
            'opciones repetidas' => [[self::condicion('prioridad', ['alta', 'alta'])], 'pasos.0.valores'],
            'una comparación que no existe' => [[self::condicion('prioridad', ['alta'], operador: 'mayor')], 'pasos.0.operador'],
            'comparar en orden una empresa' => [[self::condicion('empresa', [1], operador: 'al_menos')], 'pasos.0.operador'],
            'una lista de plazos consumidos' => [[self::condicion('plazo_respuesta_consumido', [80])], 'pasos.0.operador'],
            'comparar con dos valores' => [[self::condicion('prioridad', ['alta', 'media'], operador: 'al_menos')], 'pasos.0.valores'],
            'un porcentaje negativo' => [[self::condicion('plazo_resolucion_consumido', [-1], operador: 'al_menos')], 'pasos.0.valores.0'],
            'un camino que no es una lista' => [[['tipo' => 'condicion', 'campo' => 'prioridad', 'valores' => ['alta'], 'si' => 'asignar']], 'pasos.0.si'],
            'cuatro condiciones seguidas' => [[$cuatroSeguidas], 'pasos.0.si.0.si.0.si.0'],
            'más pasos de la cuenta' => [array_fill(0, LectorDeFlujo::MAXIMO_DE_PASOS + 1, self::asignar(7)), 'pasos.'.LectorDeFlujo::MAXIMO_DE_PASOS],
        ];
    }

    #[DataProvider('invalidos')]
    public function test_rechaza_lo_que_no_tiene_sentido_y_dice_donde(mixed $pasos, string $ruta): void
    {
        try {
            LectorDeFlujo::leer($pasos);
            $this->fail('Debió rechazar los pasos.');
        } catch (FlujoInvalido $error) {
            $this->assertSame($ruta, $error->ruta);
        }
    }

    public function test_una_variable_desconocida_dice_cuales_se_pueden_usar(): void
    {
        $this->expectExceptionMessage('No existe {telefono}. Puede usar: {nombre}, {número}, {título}, {empresa}, {prioridad}.');

        LectorDeFlujo::leer([self::responder('Te llamamos al {telefono}')]);
    }
}
