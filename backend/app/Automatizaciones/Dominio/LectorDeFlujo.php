<?php

namespace App\Automatizaciones\Dominio;

use App\Automatizaciones\Dominio\Excepciones\FlujoInvalido;

// Convierte los pasos enviados o guardados (JSON) en un Flujo. Rechaza, con la ruta exacta del dato, lo
// que no tenga sentido; los límites evitan árboles imposibles de leer.
final class LectorDeFlujo
{
    public const MAXIMO_DE_CONDICIONES_SEGUIDAS = 3;

    public const MAXIMO_DE_PASOS = 40;

    public const MAXIMO_DE_VALORES = 50;

    private int $pasosLeidos = 0;

    private function __construct() {}

    /**
     * @throws FlujoInvalido
     */
    public static function leer(mixed $pasos): Flujo
    {
        return (new self)->flujo($pasos, 'pasos', 0);
    }

    private function flujo(mixed $pasos, string $ruta, int $condicionesAntes): Flujo
    {
        if (! is_array($pasos) || ! array_is_list($pasos)) {
            throw new FlujoInvalido($ruta, 'Debe ser una lista de pasos.');
        }

        $acciones = [];
        $condicion = null;

        foreach ($pasos as $i => $paso) {
            $rutaDelPaso = "{$ruta}.{$i}";

            if (++$this->pasosLeidos > self::MAXIMO_DE_PASOS) {
                throw new FlujoInvalido($rutaDelPaso, 'Una automatización admite como máximo '.self::MAXIMO_DE_PASOS.' pasos.');
            }

            if ($condicion !== null) {
                throw new FlujoInvalido($rutaDelPaso, 'Después de una condición, los pasos van en su camino «si» o «no».');
            }

            if (! is_array($paso) || ! in_array($paso['tipo'] ?? null, ['accion', 'condicion'], true)) {
                throw new FlujoInvalido($rutaDelPaso, 'Cada paso debe ser una acción o una condición.');
            }

            if ($paso['tipo'] === 'accion') {
                $acciones[] = Accion::desde($paso, $rutaDelPaso);
            } else {
                $condicion = $this->condicion($paso, $rutaDelPaso, $condicionesAntes + 1);
            }
        }

        return new Flujo($acciones, $condicion);
    }

    /**
     * @param  array<mixed>  $paso
     */
    private function condicion(array $paso, string $ruta, int $seguidas): Condicion
    {
        if ($seguidas > self::MAXIMO_DE_CONDICIONES_SEGUIDAS) {
            throw new FlujoInvalido($ruta, 'Admite como máximo '.self::MAXIMO_DE_CONDICIONES_SEGUIDAS.' condiciones seguidas: si necesita más, cree otra automatización.');
        }

        $campo = CampoDeCondicion::tryFrom(is_string($paso['campo'] ?? null) ? $paso['campo'] : '')
            ?? throw new FlujoInvalido("{$ruta}.campo", 'Esa condición no existe.');
        $niega = $paso['niega'] ?? false;

        if (! is_bool($niega)) {
            throw new FlujoInvalido("{$ruta}.niega", 'Debe ser verdadero o falso.');
        }

        return new Condicion(
            $campo,
            $this->valores($campo, $paso['valores'] ?? null, "{$ruta}.valores"),
            $niega,
            $this->flujo($paso['si'] ?? [], "{$ruta}.si", $seguidas),
            $this->flujo($paso['no'] ?? [], "{$ruta}.no", $seguidas),
        );
    }

    /**
     * @return non-empty-list<int|string>
     */
    private function valores(CampoDeCondicion $campo, mixed $valores, string $ruta): array
    {
        if (! is_array($valores) || ! array_is_list($valores) || $valores === [] || count($valores) > self::MAXIMO_DE_VALORES) {
            throw new FlujoInvalido($ruta, 'Elija entre 1 y '.self::MAXIMO_DE_VALORES.' opciones.');
        }

        foreach ($valores as $j => $valor) {
            if (! $campo->admite($valor)) {
                throw new FlujoInvalido("{$ruta}.{$j}", 'Esa opción no sirve para esta condición.');
            }
        }

        if (count(array_unique($valores, SORT_REGULAR)) !== count($valores)) {
            throw new FlujoInvalido($ruta, 'Hay opciones repetidas.');
        }

        /** @var non-empty-list<int|string> $valores */
        return $valores;
    }
}
