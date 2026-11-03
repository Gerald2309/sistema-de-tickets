<?php

namespace App\Automatizaciones\Dominio;

// Un paso «Si»: pregunta algo del incidente; si se cumple, sigue por el camino «si» y, si no, por el «no».
final readonly class Condicion
{
    /**
     * @param  non-empty-list<int|string>  $valores  uno solo si el operador compara con un valor (≥, ≤, =)
     */
    public function __construct(
        public CampoDeCondicion $campo,
        public Operador $operador,
        public array $valores,
        public Flujo $si,
        public Flujo $no,
    ) {}

    public function seCumple(Hechos $hechos): bool
    {
        if (! $this->operador->comparaConUnValor()) {
            return $this->campo->coincide($hechos, $this->valores) === ($this->operador === Operador::EsUnaDe);
        }

        $medida = $this->campo->medidaDe($hechos);

        return $medida !== null && $this->operador->cumple($medida, $this->campo->medidaDelValor($this->valores[0]));
    }

    /**
     * @return array<string, mixed>
     */
    public function aArreglo(): array
    {
        return [
            'tipo' => 'condicion',
            'campo' => $this->campo->value,
            'operador' => $this->operador->value,
            'valores' => $this->valores,
            'si' => $this->si->aArreglo(),
            'no' => $this->no->aArreglo(),
        ];
    }
}
