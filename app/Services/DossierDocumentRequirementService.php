<?php

namespace App\Services;

use App\Models\DossierDocumentRequirement;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class DossierDocumentRequirementService
{
    public function forEntity(string $entityType, bool $activeOnly = true): Collection
    {
        $cacheKey = 'dossier_document_requirements.' . $entityType . '.' . ($activeOnly ? 'active' : 'all');

        return Cache::rememberForever($cacheKey, function () use ($entityType, $activeOnly): Collection {
            return DossierDocumentRequirement::query()
                ->where('entity_type', $entityType)
                ->when($activeOnly, fn ($query) => $query->where('is_active', true))
                ->orderBy('sort_order')
                ->orderBy('label')
                ->get();
        });
    }

    public function labelsForEntity(string $entityType): array
    {
        return $this->forEntity($entityType)
            ->mapWithKeys(fn (DossierDocumentRequirement $requirement): array => [
                $requirement->document_type => $requirement->label,
            ])
            ->all();
    }

    public function isConfigured(string $entityType, string $documentType): bool
    {
        return array_key_exists($documentType, $this->labelsForEntity($entityType));
    }

    public function labelFor(string $entityType, string $documentType): ?string
    {
        return $this->labelsForEntity($entityType)[$documentType] ?? null;
    }

    public function buildDocumentType(string $entityType, string $label, ?int $ignoreId = null): string
    {
        $base = Str::slug($label, '_');
        if ($base === '') {
            $base = 'documento';
        }

        $candidate = $base;
        $counter = 2;

        while ($this->typeExists($entityType, $candidate, $ignoreId)) {
            $candidate = $base . '_' . $counter;
            $counter++;
        }

        return $candidate;
    }

    public function flushCache(): void
    {
        foreach (array_keys(DossierDocumentRequirement::ENTITY_LABELS) as $entityType) {
            Cache::forget('dossier_document_requirements.' . $entityType . '.active');
            Cache::forget('dossier_document_requirements.' . $entityType . '.all');
        }
    }

    private function typeExists(string $entityType, string $documentType, ?int $ignoreId = null): bool
    {
        return DossierDocumentRequirement::query()
            ->where('entity_type', $entityType)
            ->where('document_type', $documentType)
            ->when($ignoreId, fn ($query) => $query->whereKeyNot($ignoreId))
            ->exists();
    }
}
