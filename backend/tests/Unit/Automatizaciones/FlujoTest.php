<?php

namespace Tests\Unit\Automatizaciones;

use App\Automatizaciones\Dominio\Accion;
use App\Automatizaciones\Dominio\Flujo;
use App\Automatizaciones\Dominio\Hechos;
use App\Automatizaciones\Dominio\LectorDeFlujo;
use App\Incidentes\Dominio\Categoria;
use App\Incidentes\Dominio\Prioridad;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class FlujoTest extends TestCase
{
    use PasosDeEjemplo;

    private const TRANSPORTES = 5;

    private const OTRA_EMPRESA = 9;

    // El del boceto: los de red de Transportes van a Diego (7) con respuesta; los demás de red, a Cristina
    // (8); lo que no es de red, solo se avisa a los supervisores (rol 2).
    private static function deRed(): Flujo
    {
        return LectorDeFlujo::leer([
            self::condicion('categoria', ['red', 'telefonia'],
                si: [self::condicion('empresa', [self::TRANSPORTES],
                    si: [self::asignar(7), self::responder('Hola {nombre}')],
                    no: [self::asignar(8)],
                )],
                no: [self::avisarAlRol(2)],
            ),
        ]);
    }

    private static function hechos(Categoria $categoria = Categoria::Red, ?int $empresa = self::TRANSPORTES, string $texto = ''): Hechos
    {
        return new Hechos($empresa, $categoria, Prioridad::Media, $texto);
    }

    /**
     * @param  list<Accion>  $acciones
     * @return list<array<string, int|string>>
     */
    private static function pasos(array $acciones): array
    {
        return array_map(fn (Accion $accion) => $accion->aArreglo(), $acciones);
    }

    /**
     * @return array<string, array{Hechos, list<array<string, mixed>>}>
     */
    public static function caminos(): array
    {
        return [
            'sí y sí' => [self::hechos(), [self::asignar(7), self::responder('Hola {nombre}')]],
            'sí y no' => [self::hechos(empresa: self::OTRA_EMPRESA), [self::asignar(8)]],
            'sí y sin empresa' => [self::hechos(empresa: null), [self::asignar(8)]],
            'no' => [self::hechos(Categoria::Correo), [self::avisarAlRol(2)]],
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $esperadas
     */
    #[DataProvider('caminos')]
    public function test_cada_incidente_sigue_el_camino_que_le_toca(Hechos $hechos, array $esperadas): void
    {
        $this->assertSame($esperadas, self::pasos(self::deRed()->accionesPara($hechos)));
    }

    public function test_las_acciones_antes_de_la_condicion_se_hacen_siempre_y_primero(): void
    {
        $flujo = LectorDeFlujo::leer([
            self::avisarAlRol(2),
            self::condicion('prioridad', ['critica'], si: [self::asignar(7)]),
        ]);

        $this->assertSame([self::avisarAlRol(2)], self::pasos($flujo->accionesPara(self::hechos())));
        $this->assertSame([self::avisarAlRol(2), self::asignar(7)], self::pasos($flujo->accionesPara(
            new Hechos(null, Categoria::Red, Prioridad::Critica, ''),
        )));
    }

    public function test_no_es_ninguna_de_invierte_el_camino(): void
    {
        $flujo = LectorDeFlujo::leer([
            self::condicion('empresa', [self::TRANSPORTES], si: [self::asignar(7)], no: [self::asignar(8)], niega: true),
        ]);

        $this->assertSame([self::asignar(8)], self::pasos($flujo->accionesPara(self::hechos())));
        $this->assertSame([self::asignar(7)], self::pasos($flujo->accionesPara(self::hechos(empresa: self::OTRA_EMPRESA))));
    }

    public function test_las_palabras_se_buscan_en_el_texto_del_incidente(): void
    {
        $flujo = LectorDeFlujo::leer([self::condicion('palabras', ['vpn', 'sin conexión'], si: [self::asignar(7)])]);

        $this->assertSame([self::asignar(7)], self::pasos($flujo->accionesPara(self::hechos(texto: 'Estoy SIN conexion desde ayer'))));
        $this->assertSame([], self::pasos($flujo->accionesPara(self::hechos(texto: 'No llega el correo'))));
    }

    public function test_un_flujo_vacio_no_hace_nada(): void
    {
        $this->assertSame([], LectorDeFlujo::leer([])->accionesPara(self::hechos()));
    }
}
