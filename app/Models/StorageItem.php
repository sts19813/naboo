<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class StorageItem extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'product_type',
        'storage_warehouse_id',
        'storage_zone_id',
        'name',
        'description',
        'brand',
        'condition',
        'quantity',
        'photo',
    ];

    public function logs(): HasMany
    {
        return $this->hasMany(StorageItemLog::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(StorageWarehouse::class, 'storage_warehouse_id');
    }

    public function zone(): BelongsTo
    {
        return $this->belongsTo(StorageZone::class, 'storage_zone_id');
    }
}
