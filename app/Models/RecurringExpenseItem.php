<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class RecurringExpenseItem extends Model
{
    use HasFactory;

    public const FREQUENCY_MONTHLY = 'monthly';

    public const FREQUENCY_ANNUAL = 'annual';

    public const FREQUENCY_ONCE = 'once';

    public const FREQUENCY_LABELS = [
        self::FREQUENCY_MONTHLY => 'Mensual',
        self::FREQUENCY_ANNUAL => 'Anual',
        self::FREQUENCY_ONCE => 'Pago único',
    ];

    protected $fillable = [
        'uuid',
        'property_id',
        'concept',
        'amount',
        'frequency',
        'starts_on',
        'occurrences_count',
        'description',
        'is_active',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'starts_on' => 'date',
            'occurrences_count' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $item): void {
            if (blank($item->uuid)) {
                $item->uuid = (string) Str::uuid();
            }
        });
    }

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class);
    }

    public function files(): HasMany
    {
        return $this->hasMany(RecurringExpenseItemFile::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getFrequencyLabelAttribute(): string
    {
        return self::FREQUENCY_LABELS[$this->frequency] ?? $this->frequency;
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }
}
