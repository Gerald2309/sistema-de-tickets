<?php

namespace App\Incidentes\Dominio;

// Texto que una persona escribió y que va a una hoja de cálculo. Excel y similares ejecutan como fórmula
// una celda que empieza por = + - @ (o por tabulador o retorno de carro), y el título o la descripción de
// un incidente los puede escribir cualquiera: se les antepone una comilla para que se lean como texto
// (OWASP, CSV Injection).
final class CeldaDeHoja
{
    private const INICIOS_PELIGROSOS = ['=', '+', '-', '@', "\t", "\r"];

    public static function segura(?string $texto): string
    {
        $texto ??= '';

        return $texto !== '' && in_array($texto[0], self::INICIOS_PELIGROSOS, true) ? "'".$texto : $texto;
    }
}
