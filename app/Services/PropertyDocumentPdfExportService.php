<?php

namespace App\Services;

use App\Models\Property;
use App\Models\PropertyDocument;
use App\Models\User;
use App\Support\PropertyVisibility;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Font;

class PropertyDocumentPdfExportService
{
    public function __construct(private readonly DossierDocumentRequirementService $requirements) {}

    /** @return array<string, mixed> */
    public function report(User $user, ?CarbonInterface $generatedAt = null): array
    {
        $properties = app(PropertyVisibility::class)->scopeVisibleToUser(Property::query(), $user)
            ->with(['documents.latestVersion'])
            ->orderBy('internal_name')
            ->get();
        $requiredDocuments = $this->requirements->labelsForEntity('property');
        $summaryRows = $properties
            ->map(fn (Property $property): array => $this->summaryRow($property, $requiredDocuments))
            ->all();
        $documentRows = $properties
            ->flatMap(fn (Property $property): Collection => $this->documentRows($property, $requiredDocuments))
            ->all();

        return [
            'generatedAt' => $generatedAt ?: now(),
            'summaryRows' => $summaryRows,
            'documentRows' => $documentRows,
            'summary' => [
                'properties' => count($summaryRows),
                'properties_with_pdf' => collect($summaryRows)->filter(fn (array $row): bool => $row['pdf_count'] > 0)->count(),
                'pdfs_loaded' => collect($summaryRows)->sum('pdf_count'),
            ],
        ];
    }

    public function generate(User $user): Spreadsheet
    {
        $report = $this->report($user);

        $spreadsheet = new Spreadsheet;
        $summarySheet = $spreadsheet->getActiveSheet();
        $summarySheet->setTitle('Resumen por propiedad');
        $this->writeSummarySheet($summarySheet, $report['summaryRows'], $report['generatedAt']);

        $documentSheet = $spreadsheet->createSheet();
        $documentSheet->setTitle('PDFs cargados');
        $this->writeDocumentSheet($documentSheet, $report['documentRows'], $report['generatedAt']);

        $spreadsheet->setActiveSheetIndex(0);

        return $spreadsheet;
    }

    /**
     * @param  array<string, string>  $requiredDocuments
     * @return array<string, mixed>
     */
    private function summaryRow(Property $property, array $requiredDocuments): array
    {
        $pdfDocuments = $this->pdfDocuments($property);
        $validPdfDocumentTypes = $pdfDocuments
            ->reject(fn (PropertyDocument $document): bool => $this->isInvalid($document))
            ->pluck('document_type')
            ->all();
        $expiredPdfDocumentTypes = $pdfDocuments
            ->filter(fn (PropertyDocument $document): bool => $this->isInvalid($document))
            ->pluck('document_type')
            ->all();
        $missingRequiredDocuments = collect($requiredDocuments)
            ->reject(fn (string $label, string $type): bool => in_array($type, $validPdfDocumentTypes, true))
            ->map(fn (string $label, string $type): string => in_array($type, $expiredPdfDocumentTypes, true)
                ? $label.' (vencido o rechazado)'
                : $label)
            ->values();

        return [
            'property' => $property,
            'pdf_count' => $pdfDocuments->count(),
            'pdf_labels' => $pdfDocuments->pluck('label')->filter()->implode("\n"),
            'required_pdf_count' => collect(array_keys($requiredDocuments))
                ->filter(fn (string $type): bool => in_array($type, $validPdfDocumentTypes, true))
                ->count(),
            'required_total' => count($requiredDocuments),
            'missing_labels' => $missingRequiredDocuments->implode("\n"),
        ];
    }

    /**
     * @param  array<string, string>  $requiredDocuments
     * @return Collection<int, array<string, mixed>>
     */
    private function documentRows(Property $property, array $requiredDocuments): Collection
    {
        return $this->pdfDocuments($property)
            ->map(function (PropertyDocument $document) use ($property, $requiredDocuments): array {
                $latestVersion = $document->latestVersion;

                return [
                    'property' => $property,
                    'document' => $document,
                    'is_required' => array_key_exists($document->document_type, $requiredDocuments),
                    'status_label' => ($document->expires_at && $document->expires_at->lt(now()->startOfDay())) ? PropertyDocument::STATUS_LABELS[PropertyDocument::STATUS_EXPIRED] : $document->status_label,
                    'file_name' => $latestVersion?->original_name ?: basename((string) $document->file_path),
                    'uploaded_at' => $document->uploaded_at ?: $latestVersion?->uploaded_at,
                    'file_url' => Storage::disk('public')->url((string) $document->file_path),
                    'version_number' => $latestVersion?->version_number,
                ];
            })
            ->values();
    }

    /** @return Collection<int, PropertyDocument> */
    private function pdfDocuments(Property $property): Collection
    {
        return $property->documents
            ->filter(fn (PropertyDocument $document): bool => filled($document->file_path))
            ->filter(fn (PropertyDocument $document): bool => $this->isPdf($document))
            ->values();
    }

    private function isPdf(PropertyDocument $document): bool
    {
        $path = Str::lower((string) $document->file_path);
        $mimeType = Str::lower((string) ($document->latestVersion?->mime_type ?? ''));

        return Str::endsWith($path, '.pdf') || $mimeType === 'application/pdf';
    }

    private function isInvalid(PropertyDocument $document): bool
    {
        return in_array($document->status, [PropertyDocument::STATUS_EXPIRED, PropertyDocument::STATUS_REJECTED], true)
            || ($document->expires_at && $document->expires_at->lt(now()->startOfDay()));
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     */
    private function writeSummarySheet($sheet, array $rows, CarbonInterface $generatedAt): void
    {
        $sheet->mergeCells('A1:K1');
        $sheet->setCellValue('A1', 'Control de documentos PDF por propiedad');
        $sheet->mergeCells('A2:K2');
        $sheet->setCellValue('A2', 'Generado el '.$generatedAt->format('d/m/Y H:i').' · Solo documentos actuales en formato PDF.');

        $propertiesWithPdf = collect($rows)->filter(fn (array $row): bool => $row['pdf_count'] > 0)->count();
        $propertiesWithoutPdf = count($rows) - $propertiesWithPdf;
        $totalPdfs = collect($rows)->sum('pdf_count');
        $sheet->fromArray([
            ['Propiedades', count($rows), 'Con PDF', $propertiesWithPdf, 'Sin PDF', $propertiesWithoutPdf, 'PDFs cargados', $totalPdfs],
        ], null, 'A4');

        $headerRow = 7;
        $sheet->fromArray([[
            'Propiedad', 'Referencia', 'Estado', 'Dirección', 'PDFs cargados', 'Documentos PDF cargados',
            'Requeridos PDF', 'Estado expediente PDF', 'Documentos requeridos sin PDF', 'Abrir expediente', 'Ubicación',
        ]], null, 'A'.$headerRow);

        foreach ($rows as $index => $row) {
            /** @var Property $property */
            $property = $row['property'];
            $currentRow = $headerRow + $index + 1;
            $missingLabels = $row['missing_labels'];
            $isComplete = $missingLabels === '';
            $values = [
                $property->internal_name,
                $property->internal_reference,
                $property->status_label,
                $property->full_address,
                $row['pdf_count'],
                $row['pdf_labels'] ?: 'Sin PDFs cargados',
                $row['required_pdf_count'].' de '.$row['required_total'],
                $isComplete ? 'Completo en PDF' : 'Pendiente',
                $missingLabels ?: 'Sin faltantes configurados',
                'Abrir expediente',
                filled($property->map_url) ? 'Abrir mapa' : 'Sin ubicación',
            ];
            foreach ($values as $column => $value) $sheet->setCellValueExplicit(chr(65 + $column).$currentRow, (string) $value, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);

            $this->setLink($sheet, 'J'.$currentRow, route('dossiers.properties.show', $property));
            if (filled($property->map_url) && in_array(strtolower((string) parse_url($property->map_url, PHP_URL_SCHEME)), ['http', 'https'], true)) {
                $this->setLink($sheet, 'K'.$currentRow, $property->map_url);
            }
        }

        $lastRow = max($headerRow + 1, $headerRow + count($rows));
        $this->styleTitle($sheet, 'A1:K1', 'A2:K2');
        $this->styleMetricRow($sheet, 'A4:H4');
        $this->styleTable($sheet, 'A'.$headerRow.':K'.$lastRow, $headerRow);
        $sheet->getStyle('F'.($headerRow + 1).':I'.$lastRow)->getAlignment()->setWrapText(true);
        $sheet->getStyle('H'.($headerRow + 1).':H'.$lastRow)->getFont()->setBold(true);

        foreach ($rows as $index => $row) {
            $currentRow = $headerRow + $index + 1;
            $color = $row['missing_labels'] === '' ? 'E8F5E9' : 'FDECEC';
            $sheet->getStyle('H'.$currentRow)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB($color);
        }

        foreach ([
            'A' => 31, 'B' => 22, 'C' => 16, 'D' => 42, 'E' => 15, 'F' => 34,
            'G' => 17, 'H' => 22, 'I' => 34, 'J' => 18, 'K' => 16,
        ] as $column => $width) {
            $sheet->getColumnDimension($column)->setWidth($width);
        }
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     */
    private function writeDocumentSheet($sheet, array $rows, CarbonInterface $generatedAt): void
    {
        $sheet->mergeCells('A1:K1');
        $sheet->setCellValue('A1', 'PDFs actualmente cargados');
        $sheet->mergeCells('A2:K2');
        $sheet->setCellValue('A2', 'Generado el '.$generatedAt->format('d/m/Y H:i').' · Un renglón por documento PDF actual.');

        $headerRow = 4;
        $sheet->fromArray([[
            'Propiedad', 'Referencia', 'Documento', 'Clasificación', 'Estado documento', 'Archivo PDF',
            'Fecha de carga', 'Vencimiento', 'Versión', 'Abrir PDF', 'Abrir expediente',
        ]], null, 'A'.$headerRow);

        foreach ($rows as $index => $row) {
            /** @var Property $property */
            $property = $row['property'];
            /** @var PropertyDocument $document */
            $document = $row['document'];
            $currentRow = $headerRow + $index + 1;
            $values = [
                $property->internal_name,
                $property->internal_reference,
                $document->label ?: $document->document_type,
                $row['is_required'] ? 'Requerido' : 'Adicional',
                $row['status_label'],
                $row['file_name'],
                $row['uploaded_at']?->format('d/m/Y H:i'),
                $document->expires_at?->format('d/m/Y'),
                $row['version_number'] ?: 'Sin historial',
                'Abrir PDF',
                'Abrir expediente',
            ];
            foreach ($values as $column => $value) $sheet->setCellValueExplicit(chr(65 + $column).$currentRow, (string) $value, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $this->setLink($sheet, 'J'.$currentRow, $row['file_url']);
            $this->setLink($sheet, 'K'.$currentRow, route('dossiers.properties.show', $property));
        }

        $lastRow = max($headerRow + 1, $headerRow + count($rows));
        $this->styleTitle($sheet, 'A1:K1', 'A2:K2');
        $this->styleTable($sheet, 'A'.$headerRow.':K'.$lastRow, $headerRow);
        $sheet->getStyle('A'.($headerRow + 1).':K'.$lastRow)->getAlignment()->setWrapText(true);

        foreach ([
            'A' => 31, 'B' => 22, 'C' => 30, 'D' => 16, 'E' => 18, 'F' => 38,
            'G' => 19, 'H' => 16, 'I' => 13, 'J' => 15, 'K' => 18,
        ] as $column => $width) {
            $sheet->getColumnDimension($column)->setWidth($width);
        }
    }

    private function styleTitle($sheet, string $titleRange, string $subtitleRange): void
    {
        $sheet->getStyle($titleRange)->getFont()->setBold(true)->setSize(15)->getColor()->setRGB('FF3366');
        $sheet->getStyle($subtitleRange)->getFont()->setItalic(true)->setSize(9)->getColor()->setRGB('4B5563');
        $sheet->getRowDimension(1)->setRowHeight(24);
        $sheet->getRowDimension(2)->setRowHeight(20);
    }

    private function styleMetricRow($sheet, string $range): void
    {
        $sheet->getStyle($range)->getFont()->setBold(true)->getColor()->setRGB('FF3366');
        $sheet->getStyle($range)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('EFF6FF');
        $sheet->getStyle($range)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    }

    private function styleTable($sheet, string $range, int $headerRow): void
    {
        $sheet->getStyle($range)->getAlignment()->setVertical(Alignment::VERTICAL_TOP);
        $sheet->getStyle('A'.$headerRow.':K'.$headerRow)->getFont()->setBold(true)->getColor()->setRGB('FFFFFF');
        $sheet->getStyle('A'.$headerRow.':K'.$headerRow)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('FF3366');
        $sheet->getStyle('A'.$headerRow.':K'.$headerRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setWrapText(true);
        $sheet->getRowDimension($headerRow)->setRowHeight(30);
        $sheet->freezePane('A'.($headerRow + 1));
        $sheet->setAutoFilter($range);
    }

    private function setLink($sheet, string $cell, string $url): void
    {
        $sheet->getCell($cell)->getHyperlink()->setUrl($url);
        $sheet->getStyle($cell)->getFont()
            ->setUnderline(Font::UNDERLINE_SINGLE)
            ->getColor()
            ->setRGB('0563C1');
    }
}
