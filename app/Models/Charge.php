<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Charge extends Model
{
    use HasFactory;

    public const TYPE_RENT = 'rent';
    public const TYPE_MAINTENANCE = 'maintenance';
    public const TYPE_PENALTY = 'penalty';
    public const TYPE_DEPOSIT_ADJUSTMENT = 'deposit_adjustment';
    public const TYPE_OTHER = 'other';

    public const STATUS_PENDING = 'pending';
    public const STATUS_IN_VALIDATION = 'in_validation';
    public const STATUS_PARTIAL = 'partial';
    public const STATUS_PAID = 'paid';
    public const STATUS_CANCELED = 'canceled';

    public const TYPE_LABELS = [
        self::TYPE_RENT => 'Renta',
        self::TYPE_MAINTENANCE => 'Mantenimiento',
        self::TYPE_PENALTY => 'Penalizacion',
        self::TYPE_DEPOSIT_ADJUSTMENT => 'Ajuste deposito',
        self::TYPE_OTHER => 'Otro',
    ];

    public const STATUS_LABELS = [
        self::STATUS_PENDING => 'Pendiente',
        self::STATUS_IN_VALIDATION => 'En validacion',
        self::STATUS_PARTIAL => 'Parcial',
        self::STATUS_PAID => 'Pagado',
        self::STATUS_CANCELED => 'Cancelado',
    ];

    public const STATUS_BADGE_CLASSES = [
        self::STATUS_PENDING => 'badge-light-warning text-warning',
        self::STATUS_IN_VALIDATION => 'badge-light-primary text-primary',
        self::STATUS_PARTIAL => 'badge-light-info text-info',
        self::STATUS_PAID => 'badge-light-success text-success',
        self::STATUS_CANCELED => 'badge-light-secondary text-secondary',
    ];

    protected $fillable = [
        'uuid',
        'property_id',
        'tenant_id',
        'type',
        'due_date',
        'amount',
        'paid_amount',
        'period_month',
        'period_year',
        'concept',
        'notes',
        'status',
        'payment_token',
        'paid_at',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'due_date' => 'date',
            'amount' => 'decimal:2',
            'paid_amount' => 'decimal:2',
            'period_month' => 'integer',
            'period_year' => 'integer',
            'paid_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $charge): void {
            if (blank($charge->uuid)) {
                $charge->uuid = (string) Str::uuid();
            }

            if (blank($charge->payment_token)) {
                $charge->payment_token = Str::random(40);
            }
        });
    }

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(ChargePayment::class);
    }

    public function getTypeLabelAttribute(): string
    {
        return self::TYPE_LABELS[$this->type] ?? ucfirst(str_replace('_', ' ', $this->type));
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUS_LABELS[$this->status] ?? ucfirst(str_replace('_', ' ', $this->status));
    }

    public function getStatusBadgeClassAttribute(): string
    {
        if ($this->is_overdue) {
            return 'badge-light-danger text-danger';
        }

        return self::STATUS_BADGE_CLASSES[$this->status] ?? 'badge-light-secondary text-secondary';
    }

    public function getDisplayStatusLabelAttribute(): string
    {
        if ($this->status === self::STATUS_IN_VALIDATION) {
            return self::STATUS_LABELS[self::STATUS_IN_VALIDATION];
        }

        if ($this->is_overdue) {
            return 'Vencido';
        }

        return $this->status_label;
    }

    public function getOutstandingAmountAttribute(): float
    {
        return max(0.0, (float) $this->amount - (float) $this->paid_amount);
    }

    public function getIsOverdueAttribute(): bool
    {
        if (!in_array($this->status, [self::STATUS_PENDING, self::STATUS_PARTIAL], true)) {
            return false;
        }

        return (bool) $this->due_date?->lt(now()->startOfDay());
    }

    public function refreshPaymentStatus(): bool
    {
        if ($this->status === self::STATUS_CANCELED) {
            return false;
        }

        $previousStatus = $this->status;
        $previousPaidAt = $this->paid_at;
        $totalPaid = (float) $this->payments()
            ->where('status', ChargePayment::STATUS_SUCCEEDED)
            ->sum('amount');
        $hasPendingValidation = $this->payments()
            ->where('status', ChargePayment::STATUS_PENDING_VALIDATION)
            ->exists();

        $nextStatus = match (true) {
            $totalPaid >= (float) $this->amount => self::STATUS_PAID,
            $hasPendingValidation => self::STATUS_IN_VALIDATION,
            $totalPaid > 0 => self::STATUS_PARTIAL,
            default => self::STATUS_PENDING,
        };

        $this->forceFill([
            'paid_amount' => min($totalPaid, (float) $this->amount),
            'status' => $nextStatus,
            'paid_at' => $nextStatus === self::STATUS_PAID
                ? ($previousPaidAt ?: now())
                : null,
        ])->save();

        return $previousStatus !== self::STATUS_PAID && $nextStatus === self::STATUS_PAID;
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }
}
