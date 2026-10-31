<?php

namespace App\Incidentes\Http\Exportacion;

use App\Incidentes\Dominio\CeldaDeHoja;
use App\Incidentes\Dominio\CodigoIncidente;
use App\Incidentes\Dominio\FormatoDeExportacion;
use App\Incidentes\Dominio\Vencimiento;
use App\Incidentes\Models\Incidente;
use DateTimeInterface;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Writer\CSV\Writer as EscritorCsv;
use OpenSpout\Writer\XLSX\Writer as EscritorXlsx;

// Escribe incidentes en CSV o Excel, fila a fila hacia la salida, sin armar el archivo en memoria.
// Los valores de estado, prioridad y categoría salen tal como los entrega la API.
class EscritorDeIncidentes
{
    private const ENCABEZADOS = [
        'Código', 'Título', 'Descripción', 'Prioridad', 'Estado', 'Categoría', 'Solución',
        'Creador', 'Asignado', 'Empresa', 'Vencimiento (UTC)', 'Vencido', 'Creado (UTC)', 'Actualizado (UTC)',
    ];

    /**
     * @param  iterable<Incidente>  $incidentes  con el creador, el asignado y la empresa ya cargados
     */
    public function escribir(FormatoDeExportacion $formato, iterable $incidentes, DateTimeInterface $ahora): void
    {
        $escritor = match ($formato) {
            FormatoDeExportacion::Csv => new EscritorCsv,
            FormatoDeExportacion::Xlsx => new EscritorXlsx,
        };

        $escritor->openToFile('php://output');
        $escritor->addRow(Row::fromValuesWithStyles(self::ENCABEZADOS, array_fill(0, count(self::ENCABEZADOS), new Style(fontBold: true))));

        foreach ($incidentes as $incidente) {
            $escritor->addRow(Row::fromValues($this->fila($incidente, $ahora)));
        }

        $escritor->close();
    }

    /**
     * @return list<string>
     */
    private function fila(Incidente $incidente, DateTimeInterface $ahora): array
    {
        return [
            CodigoIncidente::deId($incidente->id),
            CeldaDeHoja::segura($incidente->titulo),
            CeldaDeHoja::segura($incidente->descripcion),
            $incidente->prioridad->value,
            $incidente->estado->value,
            $incidente->categoria->value,
            CeldaDeHoja::segura($incidente->solucion),
            CeldaDeHoja::segura($incidente->creador->name),
            CeldaDeHoja::segura($incidente->asignado?->name),
            CeldaDeHoja::segura($incidente->cliente?->nombre),
            $incidente->fecha_vencimiento->copy()->utc()->format('Y-m-d H:i:s'),
            Vencimiento::estaVencido($incidente->fecha_vencimiento, $incidente->estado, $ahora) ? 'Sí' : 'No',
            (string) $incidente->created_at?->copy()->utc()->format('Y-m-d H:i:s'),
            (string) $incidente->updated_at?->copy()->utc()->format('Y-m-d H:i:s'),
        ];
    }
}
