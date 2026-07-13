<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PropertyInventoryItemPhotoVersion extends Model
{
    use HasFactory;

    protected $fillable = [
        'property_inventory_item_photo_id',
        'file_path',
        'file_name',
        'mime_type',
        'file_size',
        'uploaded_by',
    ];

    public function photo(): BelongsTo
    {
        return $this->belongsTo(PropertyInventoryItemPhoto::class, 'property_inventory_item_photo_id');
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
