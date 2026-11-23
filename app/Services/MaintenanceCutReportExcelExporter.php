<?php

namespace App\Services;

use Carbon\CarbonInterface;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class MaintenanceCutReportExcelExporter
{
    /**
     * @param  array<int, array<string, string>>  $rows
     * @param  array<int, array{label: string, value: string}>  $indicators
     */
    public function generate(array $rows, string $filterSummary, CarbonInterface $generatedAt, array $indicators): Spreadsheet
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Cortes de mantenimiento');
        $sheet->mergeCells('A1:H1');
        $sheet->setCellValue('A1', 'Reporte de cortes de mantenimiento');
        $sheet->mergeCells('A2:H2');
        $sheet->setCellValue('A2', 'Generado el '.$generatedAt->format('d/m/Y H:i').' · '.$filterSummary);

        foreach ($indicators as $index => $indicator) {
            $row = $index + 4;
            $sheet->mergeCells('A'.$row.':B'.$row);
            $sheet->mergeCells('C'.$row.':D'.$row);
            $sheet->setCellValue('A'.$row, $indicator['label']);
            $sheet->setCellValue('C'.$row, $indicator['value']);
        }

        $headerRow = count($indicators) + 6;
        $sheet->fromArray(['Corte', 'Proveedor / técnico', 'Pagado', 'Registró', 'Tickets', 'Mano de obra', 'Materiales', 'Total pagado'], null, 'A'.$headerRow);
        foreach ($rows as $index => $row) {
            $values = [
                $row['reference'], $row['provider'], $row['paid_at'], $row['registered_by'], $row['ticket_count'], $row['labor_total'], $row['material_total'], $row['grand_total'],
            ];
            foreach ($values as $column => $value) {
                $sheet->setCellValueExplicit(chr(65 + $column).($index + $headerRow + 1), (string) $value, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            }
        }

        $lastRow = max($headerRow + 1, count($rows) + $headerRow);
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(15)->getColor()->setRGB('FF3366');
        $sheet->getStyle('A2')->getFont()->setItalic(true)->setSize(9)->getColor()->setRGB('4B5563');
        $sheet->getStyle('A4:B8')->getFont()->setBold(true)->setSize(8)->getColor()->setRGB('1E40AF');
        $sheet->getStyle('A4:B8')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('EFF6FF');
        $sheet->getStyle('C4:D8')->getFont()->setBold(true)->setSize(11)->getColor()->setRGB('0F172A');
        $sheet->getStyle('A4:D8')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
        $sheet->getStyle('A'.$headerRow.':H'.$headerRow)->getFont()->setBold(true)->getColor()->setRGB('FFFFFF');
        $sheet->getStyle('A'.$headerRow.':H'.$headerRow)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('FF3366');
        $sheet->getStyle('A1:H'.$lastRow)->getAlignment()->setVertical(Alignment::VERTICAL_TOP)->setWrapText(true);
        $sheet->getStyle('A'.$headerRow.':H'.$headerRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->freezePane('A'.($headerRow + 1));
        $sheet->setAutoFilter('A'.$headerRow.':H'.$lastRow);
        $sheet->getRowDimension(1)->setRowHeight(24);
        $sheet->getRowDimension(2)->setRowHeight(30);

        foreach (['A' => 18, 'B' => 28, 'C' => 20, 'D' => 24, 'E' => 12, 'F' => 18, 'G' => 18, 'H' => 18] as $column => $width) {
            $sheet->getColumnDimension($column)->setWidth($width);
        }

        return $spreadsheet;
    }
}
