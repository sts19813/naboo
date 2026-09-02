<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class CashCut extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'advisor_user_id',
        'advisor_name',
        'received_by_user_id',
        'received_by_name',
        'payment_count',
        'grand_total',
        'received_at',
    ];

    protected function casts(): array
    {
        return [
            'payment_count' => 'integer',
            'grand_total' => 'decimal:2',
            'received_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $cut): void {
            if (blank($cut->uuid)) {
                $cut->uuid = (string) Str::uuid();
            }
        });
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function getDisplayReferenceAttribute(): string
    {
        return 'CORTE-EF-'.str_pad((string) $this->id, 6, '0', STR_PAD_LEFT);
    }

    public function advisor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'advisor_user_id');
    }

    public function receivedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by_user_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(CashCutItem::class);
    }
}
