<?php

namespace App\Support;

use App\Models\OwnerDocumentVersion;
use App\Models\PropertyDocumentVersion;
use App\Models\TenantDocumentVersion;

class DossierStorageUsage
{
    public static function humanBytes(int $bytes): string
    {
        if ($bytes <= 0) {
            return '0 B';
        }

        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $size = $bytes;
        $unitIndex = 0;

        while ($size >= 1024 && $unitIndex < count($units) - 1) {
            $size /= 1024;
            $unitIndex++;
        }

        return ($unitIndex === 0 ? number_format($size, 0) : number_format($size, 1)) . ' ' . $units[$unitIndex];
    }

    public function summary(): array
    {
        $usedBytes = $this->usedBytes();
        $limitGb = DossierSettings::storageLimitGb();
        $limitBytes = (int) round($limitGb * 1024 * 1024 * 1024);
        $availableBytes = max(0, $limitBytes - $usedBytes);
        $percentage = $limitBytes > 0 ? min(100, ($usedBytes / $limitBytes) * 100) : 0;

        return [
            'used_bytes' => $usedBytes,
            'used_label' => self::humanBytes($usedBytes),
            'used_exact_label' => number_format($usedBytes) . ' bytes',
            'limit_gb' => $limitGb,
            'limit_bytes' => $limitBytes,
            'limit_label' => $limitBytes > 0 ? $this->gigabytesLabel($limitGb) : 'Sin limite',
            'available_bytes' => $availableBytes,
            'available_label' => self::humanBytes($availableBytes),
            'percentage' => $percentage,
            'percentage_label' => $this->percentageLabel($percentage),
            'is_over_limit' => $limitBytes > 0 && $usedBytes > $limitBytes,
        ];
    }

    private function usedBytes(): int
    {
        return (int) PropertyDocumentVersion::query()->sum('file_size')
            + (int) TenantDocumentVersion::query()->sum('file_size')
            + (int) OwnerDocumentVersion::query()->sum('file_size');
    }

    private function gigabytesLabel(float $gigabytes): string
    {
        $decimals = floor($gigabytes) === $gigabytes ? 0 : 1;

        return number_format($gigabytes, $decimals) . ' GB';
    }

    private function percentageLabel(float $percentage): string
    {
        if ($percentage > 0 && $percentage < 0.01) {
            return '<0.01';
        }

        return number_format($percentage, $percentage > 0 && $percentage < 1 ? 2 : 1);
    }
}
