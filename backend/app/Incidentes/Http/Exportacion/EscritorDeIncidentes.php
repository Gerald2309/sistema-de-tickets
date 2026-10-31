<?php

namespace App\Incidentes\Http\Exportacion;

use App\Incidentes\Dominio\Categoria;
use App\Incidentes\Dominio\CeldaDeHoja;
use App\Incidentes\Dominio\CodigoIncidente;
use App\Incidentes\Dominio\Estado;
use App\Incidentes\Dominio\FormatoDeExportacion;
use App\Incidentes\Dominio\Prioridad;
use App\Incidentes\Dominio\Vencimiento;
use App\Incidentes\Models\Incidente;
use Carbon\CarbonInterface;
use DateTimeInterface;
use OpenSpout\Common\Entity\Cell;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Style\CellVerticalAlignment;
use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Writer\AutoFilter;
use OpenSpout\Writer\CSV\Writer as EscritorCsv;
use OpenSpout\Writer\XLSX\Entity\SheetView;
use OpenSpout\Writer\XLSX\Writer as EscritorXlsx;

// Escribe incidentes en CSV o Excel, fila a fila hacia la salida, sin armar el archivo en memoria.
// El CSV es para otros sistemas: valores tal como los entrega la API y fechas en UTC. El Excel es para
// leerlo: nombres legibles, fechas de verdad en la hora de Colombia, encabezado fijo con filtros y
// columnas a la medida de su contenido.
class EscritorDeIncidentes
{
    private const ENCABEZADOS_CSV = [
        'Código', 'Título', 'Descripción', 'Prioridad', 'Estado', 'Categoría', 'Solución',
        'Creador', 'Asignado', 'Empresa', 'Vencimiento (UTC)', 'Vencido', 'Creado (UTC)', 'Actualizado (UTC)',
    ];

    // Encabezado => ancho en caracteres.
    private const COLUMNAS_EXCEL = [
        'Código' => 12, 'Título' => 42, 'Descripción' => 60, 'Prioridad' => 11, 'Estado' => 13,
        'Categoría' => 13, 'Solución' => 50, 'Creador' => 22, 'Asignado' => 22, 'Empresa' => 22,
        'Vencimiento' => 17, 'Vencido' => 10, 'Creado' => 17, 'Actualizado' => 17,
    ];

    private const ZONA_EXCEL = 'America/Bogota';

    private const FORMATO_FECHA_EXCEL = 'dd/mm/yyyy hh:mm';

    private const COLOR_ENCABEZADO = '007DA3';

    /**
     * @param  iterable<Incidente>  $incidentes  con el creador, el asignado y la empresa ya cargados
     */
    public function escribir(FormatoDeExportacion $formato, iterable $incidentes, DateTimeInterface $ahora): void
    {
        match ($formato) {
            FormatoDeExportacion::Csv => $this->escribirCsv($incidentes, $ahora),
            FormatoDeExportacion::Xlsx => $this->escribirExcel($incidentes, $ahora),
        };
    }

    /**
     * @param  iterable<Incidente>  $incidentes
     */
    private function escribirCsv(iterable $incidentes, DateTimeInterface $ahora): void
    {
        $escritor = new EscritorCsv;
        $escritor->openToFile('php://output');
        $escritor->addRow(Row::fromValues(self::ENCABEZADOS_CSV));

        foreach ($incidentes as $incidente) {
            $escritor->addRow(Row::fromValues($this->filaCsv($incidente, $ahora)));
        }

        $escritor->close();
    }

    /**
     * @param  iterable<Incidente>  $incidentes
     */
    private function escribirExcel(iterable $incidentes, DateTimeInterface $ahora): void
    {
        $escritor = new EscritorXlsx;
        $escritor->openToFile('php://output');

        // Los anchos y la vista se fijan antes de la primera fila: así los escribe el formato.
        $hoja = $escritor->getCurrentSheet();
        $hoja->setName('Incidentes');
        $hoja->setSheetView((new SheetView)->withFreezeRow(2));
        foreach (array_values(self::COLUMNAS_EXCEL) as $indice => $ancho) {
            $hoja->setColumnWidth($ancho, $indice + 1);
        }

        $encabezado = new Style(fontBold: true, fontColor: 'FFFFFF', backgroundColor: self::COLOR_ENCABEZADO);
        $escritor->addRow(Row::fromValuesWithStyle(array_keys(self::COLUMNAS_EXCEL), $encabezado, 20));

        $texto = new Style(cellVerticalAlignment: CellVerticalAlignment::TOP);
        $fecha = new Style(cellVerticalAlignment: CellVerticalAlignment::TOP, format: self::FORMATO_FECHA_EXCEL);
        $filas = 1;

        foreach ($incidentes as $incidente) {
            $escritor->addRow(new Row(array_map(
                fn (mixed $valor) => Cell::fromValue($valor, $valor instanceof DateTimeInterface ? $fecha : $texto),
                $this->filaExcel($incidente, $ahora),
            )));
            $filas++;
        }

        $hoja->setAutoFilter(new AutoFilter(0, 1, count(self::COLUMNAS_EXCEL) - 1, $filas));
        $escritor->close();
    }

    /**
     * @return list<string>
     */
    private function filaCsv(Incidente $incidente, DateTimeInterface $ahora): array
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
            $this->vencido($incidente, $ahora),
            (string) $incidente->created_at?->copy()->utc()->format('Y-m-d H:i:s'),
            (string) $incidente->updated_at?->copy()->utc()->format('Y-m-d H:i:s'),
        ];
    }

    /**
     * @return list<string|DateTimeInterface|null>
     */
    private function filaExcel(Incidente $incidente, DateTimeInterface $ahora): array
    {
        return [
            CodigoIncidente::deId($incidente->id),
            CeldaDeHoja::segura($incidente->titulo),
            CeldaDeHoja::segura($incidente->descripcion),
            self::nombreDePrioridad($incidente->prioridad),
            self::nombreDeEstado($incidente->estado),
            self::nombreDeCategoria($incidente->categoria),
            CeldaDeHoja::segura($incidente->solucion),
            CeldaDeHoja::segura($incidente->creador->name),
            CeldaDeHoja::segura($incidente->asignado?->name) ?: 'Sin asignar',
            CeldaDeHoja::segura($incidente->cliente?->nombre),
            $this->enZonaExcel($incidente->fecha_vencimiento),
            $this->vencido($incidente, $ahora),
            $this->enZonaExcel($incidente->created_at),
            $this->enZonaExcel($incidente->updated_at),
        ];
    }

    private function vencido(Incidente $incidente, DateTimeInterface $ahora): string
    {
        return Vencimiento::estaVencido($incidente->fecha_vencimiento, $incidente->estado, $ahora) ? 'Sí' : 'No';
    }

    // Excel no guarda zona horaria: la fecha se escribe ya en la hora local de quien la lee.
    private function enZonaExcel(?CarbonInterface $fecha): ?DateTimeInterface
    {
        return $fecha?->copy()->setTimezone(self::ZONA_EXCEL)->toDateTimeImmutable();
    }

    private static function nombreDePrioridad(Prioridad $prioridad): string
    {
        return match ($prioridad) {
            Prioridad::Baja => 'Baja',
            Prioridad::Media => 'Media',
            Prioridad::Alta => 'Alta',
            Prioridad::Critica => 'Crítica',
        };
    }

    private static function nombreDeEstado(Estado $estado): string
    {
        return match ($estado) {
            Estado::Abierto => 'Abierto',
            Estado::EnProgreso => 'En progreso',
            Estado::Resuelto => 'Resuelto',
            Estado::Cerrado => 'Cerrado',
        };
    }

    private static function nombreDeCategoria(Categoria $categoria): string
    {
        return match ($categoria) {
            Categoria::Red => 'Red',
            Categoria::Correo => 'Correo',
            Categoria::Hardware => 'Hardware',
            Categoria::Software => 'Software',
            Categoria::Accesos => 'Accesos',
            Categoria::Seguridad => 'Seguridad',
            Categoria::Telefonia => 'Telefonía',
            Categoria::Otros => 'Otros',
        };
    }
}
