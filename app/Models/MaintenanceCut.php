<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class MaintenanceCut extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'paid_by_user_id',
        'ticket_count',
        'labor_total',
        'material_total',
        'grand_total',
        'paid_at',
    ];

    protected function casts(): array
    {
        return [
            'ticket_count' => 'integer',
            'labor_total' => 'decimal:2',
            'material_total' => 'decimal:2',
            'grand_total' => 'decimal:2',
            'paid_at' => 'datetime',
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
        return 'CORTE-'.str_pad((string) $this->id, 6, '0', STR_PAD_LEFT);
    }

    public function paidBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'paid_by_user_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(MaintenanceCutItem::class);
    }
}
