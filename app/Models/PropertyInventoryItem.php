<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PropertyInventoryItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'property_inventory_area_id',
        'name',
        'condition',
        'notes',
        'entry_checklist',
        'exit_checklist',
    ];

    public function area(): BelongsTo
    {
        return $this->belongsTo(PropertyInventoryArea::class, 'property_inventory_area_id');
    }

    public function photos(): HasMany
    {
        return $this->hasMany(PropertyInventoryItemPhoto::class);
    }
}
