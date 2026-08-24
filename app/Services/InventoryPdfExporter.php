<?php

namespace App\Services;

use App\Models\Property;
use Illuminate\Support\Collection;
use TCPDF;

class InventoryPdfExporter
{
    private const MARGIN = 12;

    private const BOTTOM_MARGIN = 15;

    private Property $property;

    private \DateTimeInterface $generatedAt;

    /**
     * @param  Collection<int, mixed>  $latestStatuses
     * @param  array<string, string>  $imagePreviews
     */
    public function generate(Property $property, Collection $latestStatuses, array $imagePreviews, \DateTimeInterface $generatedAt): string
    {
        $this->property = $property;
        $this->generatedAt = $generatedAt;

        $pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
        $pdf->SetCreator('Naboo');
        $pdf->SetAuthor('Naboo');
        $pdf->SetTitle('Inventario '.$property->internal_name);
        $pdf->SetMargins(self::MARGIN, self::MARGIN, self::MARGIN);
        // Page breaks are handled explicitly so every new page receives the
        // report header and table columns instead of a blank TCPDF page.
        $pdf->SetAutoPageBreak(false, self::BOTTOM_MARGIN);
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->SetCellPadding(1.2);
        $pdf->SetFont('dejavusans', '', 8);

        $this->addPage($pdf, $property, $generatedAt);
        $this->drawPropertySummary($pdf, $property);

        foreach ($property->inventoryAreas as $area) {
            $this->ensureSpace($pdf, 70);
            $pdf->SetFont('dejavusans', 'B', 11);
            $pdf->SetTextColor(255, 51, 100);
            $pdf->Cell(0, 7, $area->name, 0, 1);
            $pdf->SetTextColor(43, 47, 58);

            if ($area->notes) {
                $pdf->SetFont('dejavusans', '', 8);
                $this->ensureSpace($pdf, max(7, $pdf->getStringHeight(186, $area->notes)) + 2);
                $pdf->MultiCell(0, 0, $area->notes, 0, 'L', false, 1);
                $pdf->Ln(1);
            }

            $this->drawAreaPhotos($pdf, $area->photos, $imagePreviews);
            $this->drawItemsTable($pdf, $property, $generatedAt, $area->items, $latestStatuses, $imagePreviews);
            $pdf->Ln(3);
        }

        return $pdf->Output('', 'S');
    }

    private function addPage(TCPDF $pdf, Property $property, \DateTimeInterface $generatedAt): void
    {
        $pdf->AddPage();

        $logoPath = public_path('assets/img/naboo-logo.png');
        if (is_file($logoPath)) {
            $pdf->Image($logoPath, self::MARGIN, self::MARGIN, 22, 0);
        }

        $pdf->SetXY(39, self::MARGIN);
        $pdf->SetFont('dejavusans', 'B', 16);
        $pdf->SetTextColor(255, 51, 100);
        $pdf->Cell(0, 7, 'Reporte de Inventario', 0, 1);
        $pdf->SetX(39);
        $pdf->SetFont('dejavusans', '', 8);
        $pdf->SetTextColor(108, 114, 127);
        $pdf->Cell(0, 5, 'Naboo | Generado el '.$generatedAt->format('d/m/Y H:i'), 0, 1);
        $pdf->SetTextColor(43, 47, 58);
        $pdf->SetY(34);
    }

    private function drawPropertySummary(TCPDF $pdf, Property $property): void
    {
        $status = Property::STATUS_LABELS[$property->status] ?? strtoupper((string) $property->status);
        $tenant = $property->tenant?->full_name ?: 'Sin asignar';
        $summary = [
            ['Propiedad', $property->internal_name ?: '-'],
            ['Referencia', $property->internal_reference ?: '-'],
            ['Estatus', $status],
            ['Inquilino', $tenant],
            ['Dirección', $property->full_address ?: '-'],
        ];

        $width = 186;
        foreach ($summary as $index => [$label, $value]) {
            $this->ensureSpace($pdf, 11);
            $x = self::MARGIN;
            $y = $pdf->GetY();
            $labelWidth = $index === 4 ? 30 : 30;
            $pdf->SetFillColor(247, 248, 251);
            $pdf->SetFont('dejavusans', '', 7);
            $pdf->MultiCell($labelWidth, 9, $label, 1, 'L', true, 0, $x, $y);
            $pdf->SetFont('dejavusans', 'B', 8);
            $pdf->MultiCell($width - $labelWidth, 9, (string) $value, 1, 'L', false, 1, $x + $labelWidth, $y);
            $pdf->SetY($y + 9);
        }
        $pdf->Ln(4);
    }

    /** @param  iterable<int, mixed>  $photos */
    private function drawAreaPhotos(TCPDF $pdf, iterable $photos, array $imagePreviews): void
    {
        $paths = collect($photos)->pluck('file_path')
            ->map(fn ($path) => $this->resolveImagePath($path, $imagePreviews))
            ->filter()
            ->values();
        if ($paths->isEmpty()) {
            return;
        }

        $this->ensureSpace($pdf, 35);
        $pdf->SetFont('dejavusans', '', 7);
        $pdf->Cell(0, 5, 'Fotos del área', 0, 1);
        $columns = 4;
        $cellWidth = 45;
        foreach ($paths as $index => $path) {
            if ($index % $columns === 0) {
                $this->ensureSpace($pdf, 30);
            }
            $x = self::MARGIN + (($index % $columns) * $cellWidth);
            $y = $pdf->GetY();
            $pdf->Image($path, $x, $y, 39, 26, '', '', '', true, 150, '', false, false, 0, 'CM');
            if ($index % $columns === $columns - 1 || $index === $paths->count() - 1) {
                $pdf->SetY($y + 29);
            }
        }
        $pdf->Ln(1);
    }

    /** @param  iterable<int, mixed>  $items */
    private function drawItemsTable(TCPDF $pdf, Property $property, \DateTimeInterface $generatedAt, iterable $items, Collection $latestStatuses, array $imagePreviews): void
    {
        $items = collect($items);
        if ($items->isEmpty()) {
            $this->ensureSpace($pdf, 9);
            $pdf->SetFont('dejavusans', '', 8);
            $pdf->Cell(0, 7, 'No hay elementos registrados en esta área.', 1, 1);

            return;
        }

        $columns = [37, 25, 48, 31, 45];
        $this->ensureSpace($pdf, 50);
        $this->drawTableHeader($pdf, $columns);
        foreach ($items as $item) {
            $latest = $latestStatuses->get($item->id);
            $checkLabel = $this->checkLabel($latest);
            $texts = [
                $item->name,
                $item->condition ?: '-',
                $item->notes ?: '-',
                $checkLabel,
            ];
            $pdf->SetFont('dejavusans', '', 7);
            $textHeight = max(
                19,
                ...array_map(fn ($index) => $pdf->getStringHeight($columns[$index], $texts[$index]), array_keys($texts))
            );
            $rowHeight = max(21, $textHeight + 2);
            if ($pdf->GetY() + $rowHeight + 20 > $pdf->getPageHeight() - self::BOTTOM_MARGIN) {
                $this->addPage($pdf, $property, $generatedAt);
                $this->drawTableHeader($pdf, $columns);
            }

            $pdf->SetFont('dejavusans', '', 7);

            $x = self::MARGIN;
            $y = $pdf->GetY();
            foreach ($texts as $index => $text) {
                $pdf->SetXY($x, $y);
                $pdf->MultiCell($columns[$index], $rowHeight, $text, 1, 'L', false, 0);
                $x += $columns[$index];
            }

            $pdf->Rect($x, $y, $columns[4], $rowHeight);
            $photoPath = $this->resolveImagePath($item->photos->first()?->latestVersion?->file_path, $imagePreviews);
            if ($photoPath) {
                $pdf->Image($photoPath, $x + 1.5, $y + 1.5, 26, min(18, $rowHeight - 3), '', '', '', true, 150, '', false, false, 0, 'CM');
            } else {
                $pdf->SetXY($x + 1, $y + 1);
                $pdf->SetFont('dejavusans', '', 7);
                $pdf->Cell($columns[4] - 2, 5, 'Sin foto');
            }
            $pdf->SetY($y + $rowHeight);
        }
    }

    /** @param  array<int, float>  $columns */
    private function drawTableHeader(TCPDF $pdf, array $columns): void
    {
        $this->ensureSpace($pdf, 9);
        $headers = ['Elemento', 'Condición', 'Notas', 'Último check', 'Foto'];
        $x = self::MARGIN;
        $y = $pdf->GetY();
        $pdf->SetFillColor(247, 248, 251);
        $pdf->SetFont('dejavusans', 'B', 7);
        foreach ($headers as $index => $header) {
            $pdf->SetXY($x, $y);
            $pdf->MultiCell($columns[$index], 8, $header, 1, 'L', true, 0);
            $x += $columns[$index];
        }
        $pdf->SetY($y + 8);
    }

    private function ensureSpace(TCPDF $pdf, float $height): void
    {
        if ($pdf->GetY() + $height <= $pdf->getPageHeight() - self::BOTTOM_MARGIN) {
            return;
        }

        $this->addPage($pdf, $this->property, $this->generatedAt);
    }

    private function resolveImagePath(?string $relativePath, array $imagePreviews): ?string
    {
        if (! $relativePath) {
            return null;
        }

        $relativePath = ltrim($relativePath, '/');
        $preview = $imagePreviews[$relativePath] ?? null;

        return $preview && is_file($preview) ? $preview : null;
    }

    private function checkLabel(mixed $latest): string
    {
        if (! $latest) {
            return 'Pendiente';
        }

        $labels = ['ok' => 'OK', 'damaged' => 'Dañado', 'missing' => 'Faltante', 'pending' => 'Pendiente'];
        $date = $latest->check?->completed_at ?: $latest->check?->created_at;

        return ($labels[$latest->status] ?? strtoupper((string) $latest->status))
            .($date ? "\n".$date->format('d/m/Y H:i') : '');
    }
}
