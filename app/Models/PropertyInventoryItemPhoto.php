<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PropertyInventoryItemPhoto extends Model
{
    use HasFactory;

    public const STATUS_ACTIVE = 'active';
    public const STATUS_EXPIRED = 'expired';

    protected $fillable = [
        'property_inventory_item_id',
        'name',
        'status',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'date',
        ];
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(PropertyInventoryItem::class, 'property_inventory_item_id');
    }

    public function versions(): HasMany
    {
        return $this->hasMany(PropertyInventoryItemPhotoVersion::class);
    }

    public function latestVersion()
    {
        return $this->hasOne(PropertyInventoryItemPhotoVersion::class)->latestOfMany();
    }
}
