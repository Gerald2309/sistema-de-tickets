<?php

namespace App\Automatizaciones\Dominio;

use DateTimeInterface;

// Qué parte de un plazo del SLA ya pasó, en porcentaje: el tiempo desde la creación hasta que se cumplió (o hasta
// ahora, si sigue pendiente) frente al tiempo que daba el plazo. Pasa de 100 cuando se venció. Es tiempo de
// reloj, como el disparador «SLA por vencer».
final class ConsumoDelPlazo
{
    /**
     * @param  DateTimeInterface|null  $limite  null si el incidente no tiene ese plazo
     * @param  DateTimeInterface|null  $cumplido  cuándo se respondió o resolvió; null si sigue pendiente
     * @return int|null null sin plazo
     */
    public static function porcentaje(DateTimeInterface $inicio, ?DateTimeInterface $limite, ?DateTimeInterface $cumplido, DateTimeInterface $ahora): ?int
    {
        $total = $limite === null ? 0 : $limite->getTimestamp() - $inicio->getTimestamp();

        if ($total <= 0) {
            return null;
        }

        $transcurrido = max(0, ($cumplido ?? $ahora)->getTimestamp() - $inicio->getTimestamp());

        return intdiv($transcurrido * 100, $total);
    }
}
