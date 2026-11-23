<?php

namespace App\Services;

use Carbon\CarbonInterface;
use TCPDF;

class MaintenanceCutReportPdfExporter
{
    /**
     * @param  array<int, array<string, string>>  $rows
     * @param  array<int, array{label: string, value: string}>  $indicators
     */
    public function generate(array $rows, string $filterSummary, CarbonInterface $generatedAt, array $indicators): string
    {
        $pdf = new TCPDF('L', 'mm', 'A4', true, 'UTF-8', false);
        $pdf->SetCreator(config('app.name'));
        $pdf->SetAuthor(config('app.name'));
        $pdf->SetTitle('Reporte de cortes de mantenimiento');
        $pdf->SetMargins(10, 12, 10);
        $pdf->SetAutoPageBreak(true, 12);
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->SetFont('dejavusans', '', 8);
        $pdf->AddPage();

        $pdf->SetFont('dejavusans', 'B', 16);
        $pdf->Cell(0, 8, 'Reporte de cortes de mantenimiento', 0, 1, 'L');
        $pdf->SetFont('dejavusans', '', 8);
        $pdf->MultiCell(0, 5, 'Generado el '.$generatedAt->format('d/m/Y H:i').' · '.$filterSummary, 0, 'L', false, 1);
        $pdf->Ln(2);
        $this->drawIndicators($pdf, $indicators);
        $pdf->Ln(3);

        $widths = [32, 47, 31, 40, 18, 36, 36, 37];
        $headers = ['Corte', 'Proveedor / técnico', 'Pagado', 'Registró', 'Tickets', 'Mano de obra', 'Materiales', 'Total pagado'];
        $this->drawHeader($pdf, $headers, $widths);

        if ($rows === []) {
            $pdf->MultiCell(array_sum($widths), 10, 'No se encontraron cortes con los filtros seleccionados.', 1, 'C');
        } else {
            foreach ($rows as $row) {
                $this->drawRow($pdf, [
                    $row['reference'],
                    $row['provider'],
                    $row['paid_at'],
                    $row['registered_by'],
                    $row['ticket_count'],
                    $row['labor_total'],
                    $row['material_total'],
                    $row['grand_total'],
                ], $widths, $headers);
            }
        }

        return $pdf->Output('', 'S');
    }

    /** @param array<int, string> $headers @param array<int, int> $widths */
    private function drawHeader(TCPDF $pdf, array $headers, array $widths): void
    {
        $pdf->SetFont('dejavusans', 'B', 8);
        $pdf->SetFillColor(255, 51, 102);
        $pdf->SetTextColor(255, 255, 255);
        $height = max(10, ...array_map(
            fn (int $index, string $header): float => $pdf->getStringHeight($widths[$index] - 2, $header) + 3,
            array_keys($headers),
            $headers,
        ));
        $x = $pdf->GetX();
        $y = $pdf->GetY();

        foreach ($headers as $index => $header) {
            $pdf->MultiCell($widths[$index], $height, $header, 1, 'L', true, 0, $x, $y, true, 0, false, true, $height, 'M');
            $x += $widths[$index];
        }

        $pdf->SetXY(10, $y + $height);
        $pdf->SetTextColor(0, 0, 0);
        $pdf->SetFont('dejavusans', '', 8);
    }

    /** @param array<int, array{label: string, value: string}> $indicators */
    private function drawIndicators(TCPDF $pdf, array $indicators): void
    {
        $width = 277 / count($indicators);
        $height = 16;
        $x = $pdf->GetX();
        $y = $pdf->GetY();

        foreach ($indicators as $indicator) {
            $pdf->SetFillColor(239, 246, 255);
            $pdf->SetTextColor(30, 64, 175);
            $pdf->SetFont('dejavusans', 'B', 7);
            $pdf->MultiCell($width, 6, $indicator['label'], 1, 'L', true, 0, $x, $y, true, 0, false, true, 6, 'M');
            $pdf->SetTextColor(15, 23, 42);
            $pdf->SetFont('dejavusans', 'B', 10);
            $pdf->MultiCell($width, 10, $indicator['value'], 1, 'L', false, 0, $x, $y + 6, true, 0, false, true, 10, 'M');
            $x += $width;
        }

        $pdf->SetXY(10, $y + $height);
        $pdf->SetTextColor(0, 0, 0);
        $pdf->SetFont('dejavusans', '', 8);
    }

    /** @param array<int, string> $values @param array<int, int> $widths @param array<int, string> $headers */
    private function drawRow(TCPDF $pdf, array $values, array $widths, array $headers): void
    {
        $height = max(10, ...array_map(
            fn (int $index, string $value): float => $pdf->getStringHeight($widths[$index] - 2, $value) + 3,
            array_keys($values),
            $values,
        ));

        if ($pdf->GetY() + $height > $pdf->getPageHeight() - 12) {
            $pdf->AddPage();
            $this->drawHeader($pdf, $headers, $widths);
        }

        $x = $pdf->GetX();
        $y = $pdf->GetY();
        foreach ($values as $index => $value) {
            $pdf->MultiCell($widths[$index], $height, $value, 1, 'L', false, 0, $x, $y, true, 0, false, true, $height, 'M');
            $x += $widths[$index];
        }
        $pdf->SetXY(10, $y + $height);
    }
}
