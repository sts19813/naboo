<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <style>
        @page { margin: 18px 20px; }
        * { box-sizing: border-box; }
        body { color: #1f2937; font-family: DejaVu Sans, sans-serif; font-size: 8px; }
        h1 { color: #ff3366; font-size: 17px; margin: 0 0 4px; }
        .subtitle { color: #64748b; margin: 0 0 16px; }
        .metrics { margin: 0 0 18px; width: 100%; }
        .metrics td { background: #eff6ff; border: 1px solid #d6dee7; color: #ff3366; font-weight: bold; padding: 7px 9px; text-align: center; }
        h2 { color: #ff3366; font-size: 11px; margin: 18px 0 7px; }
        table { border-collapse: collapse; table-layout: fixed; width: 100%; }
        th { background: #ff3366; border: 1px solid #ff3366; color: #fff; font-size: 7px; padding: 6px 4px; text-align: left; }
        td { border: 1px solid #d6dee7; padding: 5px 4px; vertical-align: top; word-wrap: break-word; }
        .status-complete { background: #e8f5e9; color: #166534; font-weight: bold; }
        .status-pending { background: #fdecec; color: #991b1b; font-weight: bold; }
        .muted { color: #64748b; }
        .page-break { page-break-before: always; }
        .property-group { background: #e9f2fb; color: #ff3366; font-size: 10px; font-weight: bold; margin: 15px 0 6px; padding: 7px 9px; }
    </style>
</head>
<body>
    <h1>Control de documentos PDF por propiedad</h1>
    <p class="subtitle">Generado el {{ $generatedAt->format('d/m/Y H:i') }}. Incluye únicamente documentos actuales cargados en PDF.</p>

    <table class="metrics">
        <tr>
            <td>Propiedades<br><strong>{{ $summary['properties'] }}</strong></td>
            <td>Con PDF<br><strong>{{ $summary['properties_with_pdf'] }}</strong></td>
            <td>Sin PDF<br><strong>{{ $summary['properties'] - $summary['properties_with_pdf'] }}</strong></td>
            <td>PDFs cargados<br><strong>{{ $summary['pdfs_loaded'] }}</strong></td>
        </tr>
    </table>

    <h2>Resumen por propiedad</h2>
    <table>
        <thead>
            <tr>
                <th style="width: 18%">Propiedad</th>
                <th style="width: 11%">Referencia</th>
                <th style="width: 7%">PDFs</th>
                <th style="width: 12%">Requeridos PDF</th>
                <th style="width: 14%">Estado</th>
                <th style="width: 38%">Documentos requeridos sin PDF</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($summaryRows as $row)
                <tr>
                    <td>{{ $row['property']->internal_name }}</td>
                    <td>{{ $row['property']->internal_reference ?: '-' }}</td>
                    <td>{{ $row['pdf_count'] }}</td>
                    <td>{{ $row['required_pdf_count'] }} de {{ $row['required_total'] }}</td>
                    <td class="{{ $row['missing_labels'] === '' ? 'status-complete' : 'status-pending' }}">
                        {{ $row['missing_labels'] === '' ? 'Completo en PDF' : 'Pendiente' }}
                    </td>
                    <td>{{ $row['missing_labels'] ?: 'Sin faltantes configurados' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="page-break"></div>
    <h2>PDFs actualmente cargados</h2>
    @forelse (collect($documentRows)->groupBy(fn (array $row) => $row['property']->id) as $propertyRows)
        @php($property = $propertyRows->first()['property'])
        <div class="property-group">{{ $property->internal_name }}{{ $property->internal_reference ? ' · '.$property->internal_reference : '' }}</div>
        <table>
            <thead>
                <tr>
                    <th style="width: 29%">Documento</th>
                    <th style="width: 14%">Clasificación</th>
                    <th style="width: 15%">Estado</th>
                    <th style="width: 27%">Archivo PDF</th>
                    <th style="width: 15%">Cargado</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($propertyRows as $row)
                    <tr>
                        <td>{{ $row['document']->label ?: $row['document']->document_type }}</td>
                        <td>{{ $row['is_required'] ? 'Requerido' : 'Adicional' }}</td>
                        <td>{{ $row['status_label'] }}</td>
                        <td>{{ $row['file_name'] }}</td>
                        <td>{{ $row['uploaded_at']?->format('d/m/Y') ?: '-' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @empty
        <p class="muted">No hay documentos PDF cargados.</p>
    @endforelse
</body>
</html>
