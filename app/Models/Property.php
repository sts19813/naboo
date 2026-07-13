<?php

namespace App\Models;

use DateTimeInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class Property extends Model
{
    use HasFactory;
    private const CHANGE_LOG_IGNORED_ATTRIBUTES = [
        'updated_at',
        'created_at',
    ];
    private array $pendingPropertyChangeSet = [];

    public const STATUS_DRAFT = 'draft';
    public const STATUS_AVAILABLE = 'available';
    public const STATUS_IN_PROCESS = 'in_process';
    public const STATUS_BLOCKED = 'blocked';
    public const STATUS_OCCUPIED = 'occupied';
    public const STATUS_RENTED = 'rented';

    public const STATUS_LABELS = [
        self::STATUS_DRAFT => 'Borrador',
        self::STATUS_AVAILABLE => 'Disponible',
        self::STATUS_IN_PROCESS => 'En proceso',
        self::STATUS_BLOCKED => 'Bloqueada',
        self::STATUS_OCCUPIED => 'Ocupada',
        self::STATUS_RENTED => 'Rentada',
    ];

    public const STATUS_BADGE_CLASSES = [
        self::STATUS_DRAFT => 'badge-light-secondary text-secondary',
        self::STATUS_AVAILABLE => 'badge-light-warning text-warning',
        self::STATUS_IN_PROCESS => 'badge-light-info text-info',
        self::STATUS_BLOCKED => 'badge-light-danger text-danger',
        self::STATUS_OCCUPIED => 'badge-light-success text-success',
        self::STATUS_RENTED => 'badge-light-success text-success',
    ];

    protected $fillable = [
        'uuid',
        'internal_name',
        'internal_reference',
        'property_type_id',
        'zone_id',
        'zone_text',
        'full_address',
        'map_url',
        'complex_name',
        'official_number',
        'unit_number',
        'monthly_rent_price',
        'charge_day',
        'charge_tolerance_days',
        'use_global_expense_notifications',
        'expense_notification_days_before',
        'expense_notification_emails',
        'expense_notification_phones',
        'rent_charge_plan',
        'facade_photo_path',
        'details',
        'description',
        'rental_requirements',
        'amenities',
        'status',
        'tenant_id',
        'current_tenant_name',
        'contract_starts_at',
        'contract_expires_at',
        'onboarding_step',
        'created_by',
        'advisor_user_id',
    ];

    protected function casts(): array
    {
        return [
            'contract_starts_at' => 'date',
            'contract_expires_at' => 'date',
            'onboarding_step' => 'integer',
            'monthly_rent_price' => 'decimal:2',
            'charge_day' => 'integer',
            'charge_tolerance_days' => 'integer',
            'use_global_expense_notifications' => 'boolean',
            'expense_notification_days_before' => 'integer',
            'expense_notification_emails' => 'array',
            'expense_notification_phones' => 'array',
            'rent_charge_plan' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $property) {
            if (blank($property->uuid)) {
                $property->uuid = (string) Str::uuid();
            }
        });

        static::updating(function (self $property): void {
            $property->pendingPropertyChangeSet = $property->buildPendingPropertyChangeSet();
        });

        static::updated(function (self $property): void {
            if (empty($property->pendingPropertyChangeSet)) {
                return;
            }

            PropertyChangeLog::create([
                'property_id' => $property->id,
                'user_id' => Auth::id(),
                'change_set' => $property->pendingPropertyChangeSet,
                'changed_at' => now(),
            ]);

            $property->pendingPropertyChangeSet = [];
        });
    }

    public function type(): BelongsTo
    {
        return $this->belongsTo(PropertyType::class, 'property_type_id');
    }

    public function zone(): BelongsTo
    {
        return $this->belongsTo(Zone::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function advisor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'advisor_user_id');
    }

    public function advisors(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'property_advisor')
            ->withTimestamps();
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function owners(): BelongsToMany
    {
        return $this->belongsToMany(Owner::class)->withTimestamps();
    }

    public function documents(): HasMany
    {
        return $this->hasMany(PropertyDocument::class);
    }

    public function pendingDocuments(): HasMany
    {
        return $this->documents()->where('status', PropertyDocument::STATUS_PENDING);
    }

    public function inventoryAreas(): HasMany
    {
        return $this->hasMany(PropertyInventoryArea::class);
    }

    public function inventoryChecks(): HasMany
    {
        return $this->hasMany(InventoryCheck::class);
    }

    public function charges(): HasMany
    {
        return $this->hasMany(Charge::class);
    }

    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class);
    }

    public function recurringExpenseItems(): HasMany
    {
        return $this->hasMany(RecurringExpenseItem::class)
            ->orderByDesc('is_active')
            ->orderBy('concept');
    }

    public function maintenanceTickets(): HasMany
    {
        return $this->hasMany(MaintenanceTicket::class);
    }

    public function changeLogs(): HasMany
    {
        return $this->hasMany(PropertyChangeLog::class)->latest('changed_at')->latest('id');
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUS_LABELS[$this->status] ?? ucfirst(str_replace('_', ' ', $this->status));
    }

    public function getStatusBadgeClassAttribute(): string
    {
        return self::STATUS_BADGE_CLASSES[$this->status] ?? 'badge-light-secondary text-secondary';
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function resolvedExpenseNotificationSetup(?ExpenseNotificationSetting $globalSetup = null): array
    {
        $globalSetup = $globalSetup ?: ExpenseNotificationSetting::current();
        $usesGlobal = (bool) $this->use_global_expense_notifications;

        if ($usesGlobal) {
            return [
                'uses_global' => true,
                'days_before' => max(0, (int) ($globalSetup->days_before ?? 0)),
                'emails' => $this->normalizeContactList($globalSetup->emails ?? []),
                'phones' => $this->normalizeContactList($globalSetup->phones ?? []),
            ];
        }

        return [
            'uses_global' => false,
            'days_before' => max(0, (int) ($this->expense_notification_days_before ?? 0)),
            'emails' => $this->normalizeContactList($this->expense_notification_emails ?? []),
            'phones' => $this->normalizeContactList($this->expense_notification_phones ?? []),
        ];
    }

    private function buildPendingPropertyChangeSet(): array
    {
        $dirty = $this->getDirty();
        $changes = [];

        foreach ($dirty as $attribute => $newValue) {
            if (in_array($attribute, self::CHANGE_LOG_IGNORED_ATTRIBUTES, true)) {
                continue;
            }

            $oldValue = $this->getOriginal($attribute);
            $normalizedOldValue = $this->normalizePropertyChangeValue($oldValue);
            $normalizedNewValue = $this->normalizePropertyChangeValue($newValue);

            if ($normalizedOldValue === $normalizedNewValue) {
                continue;
            }

            $changes[$attribute] = [
                'old' => $normalizedOldValue,
                'new' => $normalizedNewValue,
            ];
        }

        return $changes;
    }

    private function normalizePropertyChangeValue(mixed $value): mixed
    {
        if ($value instanceof DateTimeInterface) {
            return $value->format('Y-m-d H:i:s');
        }

        if (is_array($value)) {
            return array_map(fn ($item) => $this->normalizePropertyChangeValue($item), $value);
        }

        if (is_bool($value) || is_int($value) || is_float($value) || is_string($value) || $value === null) {
            return $value;
        }

        return (string) $value;
    }

    private function normalizeContactList(array $items): array
    {
        return collect($items)
            ->map(fn ($item) => trim((string) $item))
            ->filter(fn ($item) => $item !== '')
            ->unique()
            ->values()
            ->all();
    }
}
