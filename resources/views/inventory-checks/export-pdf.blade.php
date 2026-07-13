<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Inventario {{ $property->internal_name }}</title>
    <style>
        @page {
            margin: 24px 24px 36px 24px;
        }

        body {
            font-family: DejaVu Sans, sans-serif;
            color: #2b2f3a;
            font-size: 11px;
            line-height: 1.35;
        }

        .header {
            border-bottom: 2px solid #a52800;
            padding-bottom: 12px;
            margin-bottom: 16px;
        }

        .header-table {
            width: 100%;
            border-collapse: collapse;
        }

        .header-table td {
            vertical-align: middle;
        }

        .logo {
            height: 48px;
        }

        .title {
            font-size: 20px;
            font-weight: bold;
            color: #a52800;
            margin: 0;
        }

        .subtitle {
            margin: 2px 0 0 0;
            color: #6c727f;
            font-size: 10px;
        }

        .meta-grid {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 16px;
        }

        .meta-grid td {
            border: 1px solid #e6e8ef;
            padding: 7px 9px;
        }

        .meta-label {
            color: #6c727f;
            font-size: 9px;
            display: block;
            margin-bottom: 2px;
        }

        .meta-value {
            font-size: 11px;
            font-weight: bold;
        }

        .section-title {
            margin: 16px 0 8px 0;
            font-size: 14px;
            color: #a52800;
        }

        .area-card {
            border: 1px solid #e6e8ef;
            border-radius: 6px;
            padding: 10px;
            margin-bottom: 14px;
            page-break-inside: avoid;
        }

        .area-title {
            font-size: 13px;
            font-weight: bold;
            margin: 0 0 4px 0;
        }

        .area-notes {
            color: #545a67;
            margin: 0 0 8px 0;
        }

        .photos-grid {
            margin-bottom: 8px;
        }

        .photo-box {
            display: inline-block;
            width: 118px;
            margin: 0 8px 8px 0;
            text-align: center;
            vertical-align: top;
        }

        .photo-box img {
            width: 118px;
            height: 88px;
            object-fit: cover;
            border: 1px solid #d8dce6;
            border-radius: 4px;
        }

        .photo-caption {
            font-size: 9px;
            color: #6c727f;
            margin-top: 2px;
        }

        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 6px;
        }

        .items-table th {
            background: #f7f8fb;
            color: #4b5160;
            text-align: left;
            border: 1px solid #e6e8ef;
            padding: 6px;
            font-size: 10px;
        }

        .items-table td {
            border: 1px solid #e6e8ef;
            padding: 6px;
            vertical-align: top;
        }

        .status-pill {
            display: inline-block;
            border-radius: 999px;
            padding: 3px 7px;
            font-size: 9px;
            font-weight: bold;
        }

        .status-ok {
            background: #e8f7ef;
            color: #118848;
        }

        .status-damaged {
            background: #fde9ec;
            color: #b4233c;
        }

        .status-missing {
            background: #fff2de;
            color: #a96500;
        }

        .status-pending {
            background: #eceff3;
            color: #4f5665;
        }

        .muted {
            color: #6c727f;
        }

        .empty {
            padding: 10px;
            background: #f7f8fb;
            border: 1px dashed #d8dce6;
            border-radius: 6px;
            color: #6c727f;
        }

        .footer {
            position: fixed;
            bottom: -20px;
            left: 0;
            right: 0;
            text-align: right;
            color: #8a91a0;
            font-size: 9px;
        }
    </style>
</head>

<body>
    @php
        $logoPath = file_exists(public_path('assets/img/Logo.png')) ? public_path('assets/img/Logo.png') : public_path('assets/img/logo.jpg');
        $statusLabels = [
            'ok' => 'OK',
            'damaged' => 'Danado',
            'missing' => 'Faltante',
            'pending' => 'Pendiente',
        ];
        $statusClass = [
            'ok' => 'status-ok',
            'damaged' => 'status-damaged',
            'missing' => 'status-missing',
            'pending' => 'status-pending',
        ];
        $statusName = \App\Models\Property::STATUS_LABELS[$property->status] ?? strtoupper((string) $property->status);
        $getStorageImagePath = function (?string $relativePath): ?string {
            if (!$relativePath) {
                return null;
            }

            $absolutePath = storage_path('app/public/' . ltrim($relativePath, '/'));
            if (!file_exists($absolutePath)) {
                return null;
            }

            return $absolutePath;
        };
    @endphp

    <div class="header">
        <table class="header-table">
            <tr>
                <td width="90">
                    @if (file_exists($logoPath))
                        <img src="{{ $logoPath }}" class="logo" alt="Logo">
                    @endif
                </td>
                <td>
                    <p class="title">Reporte de Inventario</p>
                    <p class="subtitle">SuWork | Documento generado el {{ $generatedAt->format('d/m/Y H:i') }}</p>
                </td>
            </tr>
        </table>
    </div>

    <table class="meta-grid">
        <tr>
            <td width="28%">
                <span class="meta-label">Propiedad</span>
                <span class="meta-value">{{ $property->internal_name }}</span>
            </td>
            <td width="24%">
                <span class="meta-label">Referencia</span>
                <span class="meta-value">{{ $property->internal_reference ?: '-' }}</span>
            </td>
            <td width="24%">
                <span class="meta-label">Estatus</span>
                <span class="meta-value">{{ $statusName }}</span>
            </td>
            <td width="24%">
                <span class="meta-label">Inquilino</span>
                <span class="meta-value">{{ $property->tenant?->full_name ?: 'Sin asignar' }}</span>
            </td>
        </tr>
        <tr>
            <td colspan="4">
                <span class="meta-label">Direccion</span>
                <span class="meta-value">{{ $property->full_address }}</span>
            </td>
        </tr>
    </table>

    <h2 class="section-title">Detalle por Areas</h2>

    @forelse ($property->inventoryAreas as $area)
        <div class="area-card">
            <p class="area-title">{{ $area->name }}</p>
            @if ($area->notes)
                <p class="area-notes">{{ $area->notes }}</p>
            @endif

            @if ($area->photos->isNotEmpty())
                <div class="photos-grid">
                    @foreach ($area->photos as $photo)
                        @php
                            $imgPath = $getStorageImagePath($photo->file_path);
                        @endphp
                        @if ($imgPath)
                            <div class="photo-box">
                                <img src="{{ $imgPath }}" alt="Foto de area">
                                <div class="photo-caption">Foto de area</div>
                            </div>
                        @endif
                    @endforeach
                </div>
            @endif

            @if ($area->items->isEmpty())
                <div class="empty">No hay elementos registrados en esta area.</div>
            @else
                <table class="items-table">
                    <thead>
                        <tr>
                            <th width="18%">Elemento</th>
                            <th width="14%">Condicion</th>
                            <th width="26%">Notas del Inventario</th>
                            <th width="14%">Estatus en Check</th>
                            <th width="28%">Fotos</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($area->items as $item)
                            @php
                                $latestCheckItem = $latestStatuses->get($item->id);
                                $itemStatus = $latestCheckItem?->status ?? 'pending';
                            @endphp
                            <tr>
                                <td><strong>{{ $item->name }}</strong></td>
                                <td>{{ $item->condition ?: '-' }}</td>
                                <td>{{ $item->notes ?: '-' }}</td>
                                <td>
                                    <span class="status-pill {{ $statusClass[$itemStatus] ?? 'status-pending' }}">
                                        {{ $statusLabels[$itemStatus] ?? strtoupper((string) $itemStatus) }}
                                    </span>
                                    @if ($latestCheckItem?->check)
                                        <div class="muted" style="margin-top:4px;">
                                            {{ $latestCheckItem->check->type === 'entry' ? 'Entrada' : 'Salida' }}
                                            | {{ optional($latestCheckItem->check->completed_at)->format('d/m/Y H:i') ?: $latestCheckItem->check->created_at->format('d/m/Y H:i') }}
                                        </div>
                                    @endif
                                </td>
                                <td>
                                    @php
                                        $itemPhotoPath = $item->photos->first()?->latestVersion?->file_path;
                                        $itemImgPath = $getStorageImagePath($itemPhotoPath);
                                    @endphp

                                    @if ($itemImgPath)
                                        <img src="{{ $itemImgPath }}" alt="Foto item" style="width: 100px; height: 72px; border:1px solid #d8dce6; border-radius:4px; object-fit: cover;">
                                    @else
                                        <span class="muted">Sin foto</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>
    @empty
        <div class="empty">Esta propiedad no tiene areas de inventario registradas.</div>
    @endforelse

    <div class="footer">
        {{ $property->internal_name }} | Reporte de inventario
    </div>
</body>

</html>
