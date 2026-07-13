<?php

namespace App\Services;

use App\Models\Property;
use Illuminate\Support\Collection;

class PropertyControlService
{
    private const CHECK_LABELS = [
        'general_info' => 'Información general',
        'advisor' => 'Asesores responsables',
        'owner' => 'Propietario',
        'tenant' => 'Inquilino',
        'contract' => 'Contrato',
        'charges' => 'Cobranza',
        'inventory' => 'Inventario',
        'property_dossier' => 'Expediente de propiedad',
        'owner_dossier' => 'Expediente de propietario',
        'tenant_dossier' => 'Expediente de inquilino',
    ];

    public function buildCollection(Collection $properties): Collection
    {
        return $properties->map(fn (Property $property) => $this->buildSnapshot($property));
    }

    public function buildSnapshot(Property $property): array
    {
        $inventoryItemsCount = $property->inventoryAreas
            ->sum(fn ($area) => $area->items->count());

        $checks = [
            'general_info' => $this->hasGeneralInfo($property),
            'advisor' => $property->advisors->isNotEmpty() || filled($property->advisor_user_id),
            'owner' => $property->owners->isNotEmpty(),
            'tenant' => filled($property->tenant_id),
            'contract' => filled($property->contract_starts_at) && filled($property->contract_expires_at),
            'charges' => $property->charges->isNotEmpty(),
            'inventory' => $inventoryItemsCount > 0,
            'property_dossier' => $property->documents->contains(fn ($document) => filled($document->file_path)),
            'owner_dossier' => $property->owners->contains(
                fn ($owner) => $owner->documents->contains(fn ($document) => filled($document->file_path))
            ),
            'tenant_dossier' => $property->tenant && $property->tenant->documents->contains(
                fn ($document) => filled($document->file_path)
            ),
        ];

        $completedChecks = collect($checks)->filter()->count();
        $totalChecks = count($checks);
        $progressPercent = (int) round(($completedChecks / max(1, $totalChecks)) * 100);
        $missingChecks = collect(self::CHECK_LABELS)
            ->filter(fn ($label, $key) => !($checks[$key] ?? false))
            ->values();

        [$statusLabel, $statusTone] = match (true) {
            $progressPercent === 100 => ['Completa', 'success'],
            $progressPercent >= 60 => ['En proceso', 'warning'],
            default => ['Info faltante', 'danger'],
        };

        return [
            'property' => $property,
            'checks' => $checks,
            'progress_percent' => $progressPercent,
            'completed_checks' => $completedChecks,
            'total_checks' => $totalChecks,
            'missing_labels' => $missingChecks->all(),
            'status_label' => $statusLabel,
            'status_tone' => $statusTone,
            'advisor_name' => $property->advisors->pluck('name')->implode(', ') ?: $property->advisor?->name,
            'tenant_name' => $property->tenant?->full_name ?: $property->current_tenant_name,
            'inventory_items_count' => $inventoryItemsCount,
            'search_text' => mb_strtolower(implode(' ', array_filter([
                $property->internal_name,
                $property->internal_reference,
                $property->full_address,
                $property->advisors->pluck('name')->implode(' '),
                $property->advisor?->name,
                $property->tenant?->full_name,
            ]))),
            'is_complete' => $progressPercent === 100,
            'has_dossier_gap' => !$checks['property_dossier'] || !$checks['owner_dossier'] || !$checks['tenant_dossier'],
        ];
    }

    public function checkLabels(): array
    {
        return self::CHECK_LABELS;
    }

    private function hasGeneralInfo(Property $property): bool
    {
        return filled($property->internal_name)
            && filled($property->property_type_id)
            && (filled($property->zone_id) || filled($property->zone_text))
            && filled($property->full_address)
            && filled($property->status)
            && (float) ($property->monthly_rent_price ?? 0) > 0
            && filled($property->facade_photo_path);
    }
}
