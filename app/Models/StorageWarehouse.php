<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StorageWarehouse extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'location',
        'maps_url',
        'is_default',
    ];

    protected function casts(): array
    {
        return [
            'is_default' => 'boolean',
        ];
    }

    public function zones(): HasMany
    {
        return $this->hasMany(StorageZone::class, 'storage_warehouse_id')->orderBy('name');
    }

    public function items(): HasMany
    {
        return $this->hasMany(StorageItem::class, 'storage_warehouse_id');
    }
}
