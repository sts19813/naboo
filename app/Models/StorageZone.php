<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StorageZone extends Model
{
    use HasFactory;

    protected $fillable = [
        'storage_warehouse_id',
        'name',
        'is_default',
    ];

    protected function casts(): array
    {
        return [
            'is_default' => 'boolean',
        ];
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(StorageWarehouse::class, 'storage_warehouse_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(StorageItem::class, 'storage_zone_id');
    }
}
