<?php

namespace App\Incidentes\Dominio;

// Formatos en que se pueden descargar los incidentes.
enum FormatoDeExportacion: string
{
    case Csv = 'csv';
    case Xlsx = 'xlsx';

    public function tipoDeContenido(): string
    {
        return match ($this) {
            self::Csv => 'text/csv; charset=UTF-8',
            self::Xlsx => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        };
    }
}
