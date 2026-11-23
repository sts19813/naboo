<?php

namespace App\Services;

use Carbon\CarbonInterface;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class MaintenanceTicketReportExcelExporter
{
    /**
     * @param  array<int, array<string, string>>  $rows
     */
    public function generate(array $rows, string $filterSummary, CarbonInterface $generatedAt, ?array $indicators = null): Spreadsheet
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Tickets de mantenimiento');

        $sheet->mergeCells('A1:H1');
        $sheet->setCellValue('A1', 'Reporte de tickets de mantenimiento');
        $sheet->mergeCells('A2:H2');
        $sheet->setCellValue('A2', 'Generado el '.$generatedAt->format('d/m/Y H:i').' · '.$filterSummary);
        $headerRow = 4;

        if ($indicators !== null) {
            foreach (['A', 'C', 'E', 'G'] as $index => $column) {
                $nextColumn = chr(ord($column) + 1);
                $sheet->mergeCells($column.'4:'.$nextColumn.'4');
                $sheet->mergeCells($column.'5:'.$nextColumn.'5');
                $sheet->setCellValue($column.'4', $indicators[$index]['label']);
                $sheet->setCellValue($column.'5', $indicators[$index]['value']);
            }
            $sheet->getStyle('A4:H4')->getFont()->setBold(true)->setSize(8)->getColor()->setRGB('1E40AF');
            $sheet->getStyle('A4:H4')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('EFF6FF');
            $sheet->getStyle('A5:H5')->getFont()->setBold(true)->setSize(11)->getColor()->setRGB('0F172A');
            $sheet->getStyle('A4:H5')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
            $headerRow = 7;
        }

        $sheet->fromArray(['Folio', 'Propiedad', 'Solicitud', 'Técnico asignado', 'Estatus', 'Creación', 'Completado', 'Corte'], null, 'A'.$headerRow);

        foreach ($rows as $index => $row) {
            $values = [
                $row['folio'],
                $row['property'],
                $row['title'],
                $row['technician'],
                $row['status'],
                $row['created_at'],
                $row['completed_at'],
                $row['cut'],
            ];
            foreach ($values as $column => $value) {
                $sheet->setCellValueExplicit(chr(65 + $column).($index + $headerRow + 1), (string) $value, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            }
        }

        $lastRow = max($headerRow + 1, count($rows) + $headerRow);
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(15)->getColor()->setRGB('FF3366');
        $sheet->getStyle('A2')->getFont()->setItalic(true)->setSize(9)->getColor()->setRGB('4B5563');
        $sheet->getStyle('A'.$headerRow.':H'.$headerRow)->getFont()->setBold(true)->getColor()->setRGB('FFFFFF');
        $sheet->getStyle('A'.$headerRow.':H'.$headerRow)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('FF3366');
        $sheet->getStyle('A1:H'.$lastRow)->getAlignment()->setVertical(Alignment::VERTICAL_TOP)->setWrapText(true);
        $sheet->getStyle('A'.$headerRow.':H'.$headerRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->freezePane('A'.($headerRow + 1));
        $sheet->setAutoFilter('A'.$headerRow.':H'.$lastRow);
        $sheet->getRowDimension(1)->setRowHeight(24);
        $sheet->getRowDimension(2)->setRowHeight(30);

        foreach (['A' => 17, 'B' => 28, 'C' => 42, 'D' => 25, 'E' => 18, 'F' => 19, 'G' => 19, 'H' => 24] as $column => $width) {
            $sheet->getColumnDimension($column)->setWidth($width);
        }

        return $spreadsheet;
    }
}
