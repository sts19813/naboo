<?php

namespace App\Support;

use App\Models\SystemSetting;

class DossierSettings
{
    public static function get(string $key, mixed $fallback = null): mixed
    {
        $value = SystemSetting::query()->where('key', $key)->value('value');

        if ($value !== null) {
            return $value;
        }

        return $fallback ?? self::defaults()[$key] ?? null;
    }

    public static function setMany(array $settings): void
    {
        foreach ($settings as $key => $value) {
            SystemSetting::query()->updateOrCreate(
                ['key' => $key],
                ['value' => (string) $value],
            );
        }
    }

    public static function storageLimitGb(): float
    {
        return max(0, (float) self::get('dossiers.storage_limit_gb'));
    }

    public static function maxFileSizeMb(): int
    {
        return max(1, (int) self::get('dossiers.max_file_size_mb'));
    }

    public static function storageWarningPercent(): int
    {
        return min(100, max(50, (int) self::get('dossiers.storage_warning_percent')));
    }

    public static function uploadLimit(): array
    {
        $configuredBytes = self::maxFileSizeMb() * 1024 * 1024;
        $serverBytes = self::serverUploadLimitBytes();
        $effectiveBytes = $serverBytes > 0 ? min($configuredBytes, $serverBytes) : $configuredBytes;

        return [
            'configured_bytes' => $configuredBytes,
            'configured_label' => DossierStorageUsage::humanBytes($configuredBytes),
            'server_bytes' => $serverBytes,
            'server_label' => $serverBytes > 0 ? DossierStorageUsage::humanBytes($serverBytes) : 'Sin limite detectado',
            'effective_bytes' => $effectiveBytes,
            'effective_kilobytes' => max(1, (int) floor($effectiveBytes / 1024)),
            'effective_label' => DossierStorageUsage::humanBytes($effectiveBytes),
            'is_server_limited' => $serverBytes > 0 && $serverBytes < $configuredBytes,
        ];
    }

    public static function defaults(): array
    {
        return [
            'dossiers.storage_limit_gb' => 20,
            'dossiers.max_file_size_mb' => 100,
            'dossiers.storage_warning_percent' => 80,
        ];
    }

    private static function serverUploadLimitBytes(): int
    {
        $uploadMax = self::iniBytes((string) ini_get('upload_max_filesize'));
        $postMax = self::iniBytes((string) ini_get('post_max_size'));
        $limits = array_filter([$uploadMax, $postMax], fn (int $bytes) => $bytes > 0);

        return $limits === [] ? 0 : min($limits);
    }

    private static function iniBytes(string $value): int
    {
        $value = trim($value);

        if ($value === '') {
            return 0;
        }

        $unit = strtolower(substr($value, -1));
        $number = (float) $value;

        return match ($unit) {
            'g' => (int) ($number * 1024 * 1024 * 1024),
            'm' => (int) ($number * 1024 * 1024),
            'k' => (int) ($number * 1024),
            default => (int) $number,
        };
    }
}
