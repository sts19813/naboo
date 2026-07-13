<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PropertyInventoryArea extends Model
{
    use HasFactory;

    protected $fillable = [
        'property_id',
        'name',
        'notes',
    ];

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(PropertyInventoryItem::class);
    }

    public function photos(): HasMany
    {
        return $this->hasMany(PropertyInventoryPhoto::class);
    }
}

