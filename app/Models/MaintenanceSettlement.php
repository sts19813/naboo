<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;

class MaintenanceSettlement extends Model
{
    use HasFactory;

    public const STATUS_SETTLED = 'liquidado';

    protected $fillable = [
        'uuid',
        'reference',
        'created_by_user_id',
        'status',
        'total_tickets',
        'total_labor_cost',
        'total_material_cost',
        'total_advance_cost',
        'total_amount',
        'currency',
        'notes',
        'settled_at',
    ];

    protected function casts(): array
    {
        return [
            'total_labor_cost' => 'decimal:2',
            'total_material_cost' => 'decimal:2',
            'total_advance_cost' => 'decimal:2',
            'total_amount' => 'decimal:2',
            'settled_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $settlement): void {
            if (blank($settlement->uuid)) {
                $settlement->uuid = (string) Str::uuid();
            }
        });
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function tickets(): BelongsToMany
    {
        return $this->belongsToMany(
            MaintenanceTicket::class,
            'maintenance_settlement_ticket',
            'maintenance_settlement_id',
            'maintenance_ticket_id'
        )
            ->withPivot(['labor_cost', 'material_cost', 'advance_cost', 'final_cost'])
            ->withTimestamps();
    }
}
