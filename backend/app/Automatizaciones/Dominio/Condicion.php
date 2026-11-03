<?php

namespace App\Automatizaciones\Dominio;

// Un paso «Si»: pregunta algo del incidente y lo manda por el camino «si» o por el camino «no».
final readonly class Condicion
{
    /**
     * @param  non-empty-list<int|string>  $valores
     * @param  bool  $niega  «no es ninguna de» en lugar de «es una de»
     */
    public function __construct(
        public CampoDeCondicion $campo,
        public array $valores,
        public bool $niega,
        public Flujo $si,
        public Flujo $no,
    ) {}

    public function caminoPara(Hechos $hechos): Flujo
    {
        return $this->campo->coincide($hechos, $this->valores) !== $this->niega ? $this->si : $this->no;
    }

    /**
     * @return array<string, mixed>
     */
    public function aArreglo(): array
    {
        return [
            'tipo' => 'condicion',
            'campo' => $this->campo->value,
            'valores' => $this->valores,
            'niega' => $this->niega,
            'si' => $this->si->aArreglo(),
            'no' => $this->no->aArreglo(),
        ];
    }
}
