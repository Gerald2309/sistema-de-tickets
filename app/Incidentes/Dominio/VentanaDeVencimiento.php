<?php

namespace App\Incidentes\Dominio;

use DateTimeInterface;

// «Por vencer»: los que vencen entre `desde` y `hasta` (ahora y dentro de N horas). Los vence el reloj,
// pero quien la crea es quien conoce la hora actual: aquí no se consulta.
final readonly class VentanaDeVencimiento
{
    public function __construct(public DateTimeInterface $desde, public DateTimeInterface $hasta) {}
}
