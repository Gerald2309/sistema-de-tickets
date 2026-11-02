<?php

namespace App\Incidentes\Eventos;

// Alguien cambió el acuerdo de servicio de una empresa. Incidentes no conoce la auditoría: solo avisa, y el
// AppServiceProvider deja cada cambio en ella.
final readonly class AcuerdoDeServicioCambiado
{
    /**
     * @param  list<string>  $cambios  una línea por cambio, con el valor anterior y el nuevo
     */
    public function __construct(
        public int $autorId,
        public string $empresa,
        public array $cambios,
        public ?string $ip,
    ) {}
}
