<?php

namespace App\Support;

use App\Models\SystemSetting;
use App\Models\User;

class NotificationSettings
{
    public const ROLE_ADMIN = 'administradores';
    public const ROLE_ADVISOR = 'asesores';
    public const ROLE_TENANT = 'inquilinos';
    public const ROLE_TECHNICIAN = 'tecnicos';

    public const EVENT_ACCOUNT_CREATED = 'account_created';
    public const EVENT_PAYMENT_CONFIRMED = 'payment_confirmed';
    public const EVENT_PAYMENT_REMINDER = 'payment_reminder';
    public const EVENT_MAINTENANCE_CREATED = 'maintenance_created';
    public const EVENT_MAINTENANCE_UPDATED = 'maintenance_updated';
    public const EVENT_MAINTENANCE_MESSAGE = 'maintenance_message';
    public const EVENT_EXPENSE_UPCOMING = 'expense_upcoming';
    public const EVENT_EXPENSE_OVERDUE = 'expense_overdue';

    private const SETTING_KEY = 'notifications.email_matrix';

    public static function roles(): array
    {
        return [
            self::ROLE_ADMIN => [
                'label' => 'Administradores',
                'description' => 'Usuarios con rol administrador o admin.',
                'icon' => 'bi-shield-check',
            ],
            self::ROLE_ADVISOR => [
                'label' => 'Asesores',
                'description' => 'Usuarios asignados como asesores de propiedades.',
                'icon' => 'bi-person-badge',
            ],
            self::ROLE_TENANT => [
                'label' => 'Inquilinos',
                'description' => 'Contactos vinculados como inquilinos.',
                'icon' => 'bi-people',
            ],
            self::ROLE_TECHNICIAN => [
                'label' => 'Técnicos',
                'description' => 'Usuarios o proveedores técnicos de mantenimiento.',
                'icon' => 'bi-tools',
            ],
        ];
    }

    public static function events(): array
    {
        return [
            self::EVENT_ACCOUNT_CREATED => [
                'label' => 'Tu cuenta ha sido creada',
                'description' => 'Correo con credenciales de acceso cuando se genera una cuenta.',
            ],
            self::EVENT_PAYMENT_CONFIRMED => [
                'label' => 'Pago confirmado',
                'description' => 'Confirmación cuando una cobranza queda pagada.',
            ],
            self::EVENT_PAYMENT_REMINDER => [
                'label' => 'Recordatorio de pago',
                'description' => 'Aviso manual de cobranza pendiente o próxima a vencer.',
            ],
            self::EVENT_MAINTENANCE_CREATED => [
                'label' => 'Ticket de mantenimiento creado',
                'description' => 'Aviso al levantar un nuevo reporte de mantenimiento.',
            ],
            self::EVENT_MAINTENANCE_UPDATED => [
                'label' => 'Ticket de mantenimiento actualizado',
                'description' => 'Cambios de estado, asignaciones y cierres.',
            ],
            self::EVENT_MAINTENANCE_MESSAGE => [
                'label' => 'Nuevo mensaje de mantenimiento',
                'description' => 'Correo al recibir un mensaje dentro del ticket.',
            ],
            self::EVENT_EXPENSE_UPCOMING => [
                'label' => 'Gasto por vencer',
                'description' => 'Aviso previo de gastos configurados.',
            ],
            self::EVENT_EXPENSE_OVERDUE => [
                'label' => 'Gasto vencido',
                'description' => 'Aviso cuando un gasto supera su fecha límite.',
            ],
        ];
    }

    public static function matrix(): array
    {
        $stored = SystemSetting::query()->where('key', self::SETTING_KEY)->value('value');
        $decoded = is_string($stored) ? json_decode($stored, true) : null;

        return self::normalizeMatrix(is_array($decoded) ? $decoded : []);
    }

    public static function setMatrix(array $matrix): void
    {
        SystemSetting::query()->updateOrCreate(
            ['key' => self::SETTING_KEY],
            ['value' => json_encode(self::normalizeMatrix($matrix))],
        );
    }

    public static function allows(string $role, string $event): bool
    {
        $matrix = self::matrix();

        return (bool) ($matrix[$role][$event] ?? true);
    }

    public static function roleForUser(?User $user): ?string
    {
        if (!$user) {
            return null;
        }

        if ($user->hasRole('administrador') || $user->hasRole('admin')) {
            return self::ROLE_ADMIN;
        }

        if ($user->hasRole('asesores') || $user->hasRole('asesor') || $user->hasRole('advisor')) {
            return self::ROLE_ADVISOR;
        }

        if ($user->hasRole('inquilino') || $user->hasRole('tenant')) {
            return self::ROLE_TENANT;
        }

        if ($user->hasRole('tecnico') || $user->hasRole('technician')) {
            return self::ROLE_TECHNICIAN;
        }

        return null;
    }

    public static function defaults(): array
    {
        return collect(array_keys(self::roles()))
            ->mapWithKeys(fn (string $role): array => [
                $role => collect(array_keys(self::events()))
                    ->mapWithKeys(fn (string $event): array => [$event => true])
                    ->all(),
            ])
            ->all();
    }

    private static function normalizeMatrix(array $matrix): array
    {
        $defaults = self::defaults();

        foreach ($defaults as $role => $events) {
            foreach ($events as $event => $defaultValue) {
                $defaults[$role][$event] = array_key_exists($event, $matrix[$role] ?? [])
                    ? (bool) $matrix[$role][$event]
                    : $defaultValue;
            }
        }

        return $defaults;
    }
}
