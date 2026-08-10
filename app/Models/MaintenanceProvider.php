<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class MaintenanceProvider extends Model
{
    use HasFactory;

    public const TYPE_LABELS = [
        'tecnico_interno' => 'Técnico interno',
        'empresa_externa' => 'Empresa externa',
        'proveedor' => 'Proveedor',
    ];

    protected $fillable = [
        'uuid',
        'user_id',
        'type',
        'name',
        'email',
        'phone',
        'specialty',
        'average_cost',
        'rating',
        'availability',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'average_cost' => 'decimal:2',
            'rating' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $provider): void {
            if (blank($provider->uuid)) {
                $provider->uuid = (string) Str::uuid();
            }
        });
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(MaintenanceTicketAssignment::class, 'provider_id');
    }

    public function currentAssignments(): HasMany
    {
        return $this->assignments()->where('is_current', true);
    }
}
