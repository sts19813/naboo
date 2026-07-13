<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryCheckItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'inventory_check_id',
        'property_inventory_item_id',
        'item_name',
        'status',
        'notes',
        'photo_path',
    ];

    public function check(): BelongsTo
    {
        return $this->belongsTo(InventoryCheck::class, 'inventory_check_id');
    }

    public function inventoryItem(): BelongsTo
    {
        return $this->belongsTo(PropertyInventoryItem::class, 'property_inventory_item_id');
    }
}
