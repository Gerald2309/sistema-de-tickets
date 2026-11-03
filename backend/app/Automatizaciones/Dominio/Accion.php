<?php

namespace App\Automatizaciones\Dominio;

use App\Automatizaciones\Dominio\Excepciones\FlujoInvalido;

// Un paso «Entonces»: qué hacer y con qué datos (solo los que ese tipo necesita).
final readonly class Accion
{
    /**
     * @param  array<string, int|string>  $datos
     */
    private function __construct(public TipoDeAccion $tipo, public array $datos) {}

    /**
     * @param  array<mixed>  $paso
     *
     * @throws FlujoInvalido
     */
    public static function desde(array $paso, string $ruta): self
    {
        $tipo = TipoDeAccion::tryFrom(is_string($paso['accion'] ?? null) ? $paso['accion'] : '')
            ?? throw new FlujoInvalido("{$ruta}.accion", 'Esa acción no existe.');
        $datos = [];

        foreach ($tipo->datos() as $clave => $dato) {
            $valor = $paso[$clave] ?? null;
            $problema = $dato->problemaCon($valor);

            if ($problema !== null) {
                throw new FlujoInvalido("{$ruta}.{$clave}", $problema);
            }

            /** @var int|string $valor */
            $datos[$clave] = $valor;
        }

        return new self($tipo, $datos);
    }

    /**
     * @return array<string, int|string>
     */
    public function aArreglo(): array
    {
        return ['tipo' => 'accion', 'accion' => $this->tipo->value, ...$this->datos];
    }
}
