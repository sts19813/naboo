<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Expense extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';

    public const STATUS_OVERDUE = 'overdue';

    public const STATUS_PAID = 'paid';

    public const STATUS_LABELS = [
        self::STATUS_PENDING => 'Pendiente',
        self::STATUS_OVERDUE => 'Atrasado',
        self::STATUS_PAID => 'Pagado',
    ];

    public const STATUS_BADGE_CLASSES = [
        self::STATUS_PENDING => 'badge-light-warning text-warning',
        self::STATUS_OVERDUE => 'badge-light-danger text-danger',
        self::STATUS_PAID => 'badge-light-success text-success',
    ];

    protected $fillable = [
        'uuid',
        'property_id',
        'recurring_expense_item_id',
        'concept',
        'amount',
        'excluded_from_totals',
        'due_date',
        'recurrence_date',
        'description',
        'paid_at',
        'upcoming_notified_at',
        'overdue_notified_at',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'excluded_from_totals' => 'boolean',
            'due_date' => 'date',
            'recurrence_date' => 'date',
            'paid_at' => 'datetime',
            'upcoming_notified_at' => 'datetime',
            'overdue_notified_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $expense): void {
            if (blank($expense->uuid)) {
                $expense->uuid = (string) Str::uuid();
            }
        });
    }

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    public function recurringItem(): BelongsTo
    {
        return $this->belongsTo(RecurringExpenseItem::class, 'recurring_expense_item_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function files(): HasMany
    {
        return $this->hasMany(ExpenseFile::class);
    }

    public function scopePaid(Builder $query): Builder
    {
        return $query->whereNotNull('paid_at');
    }

    public function scopeIncludedInTotals(Builder $query): Builder
    {
        return $query->where('excluded_from_totals', false);
    }

    public function scopeUnpaid(Builder $query): Builder
    {
        return $query->whereNull('paid_at');
    }

    public function scopePending(Builder $query): Builder
    {
        return $query
            ->whereNull('paid_at')
            ->whereDate('due_date', '>=', now()->toDateString());
    }

    public function scopeOverdue(Builder $query): Builder
    {
        return $query
            ->whereNull('paid_at')
            ->whereDate('due_date', '<', now()->toDateString());
    }

    public function scopeUpcomingFirst(Builder $query): Builder
    {
        return $query
            ->orderByRaw('CASE WHEN paid_at IS NULL THEN 0 ELSE 1 END ASC')
            ->orderBy('due_date', 'asc')
            ->orderBy('id', 'asc');
    }

    public function getComputedStatusAttribute(): string
    {
        if ($this->paid_at) {
            return self::STATUS_PAID;
        }

        $isOverdue = (bool) $this->due_date?->lt(now()->startOfDay());

        return $isOverdue ? self::STATUS_OVERDUE : self::STATUS_PENDING;
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUS_LABELS[$this->computed_status] ?? 'Pendiente';
    }

    public function getStatusBadgeClassAttribute(): string
    {
        return self::STATUS_BADGE_CLASSES[$this->computed_status] ?? 'badge-light-secondary text-secondary';
    }

    public function getIsPaidAttribute(): bool
    {
        return $this->paid_at !== null;
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }
}
