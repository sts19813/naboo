<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PropertyLogbookAttachment extends Model
{
    use HasFactory;

    protected $fillable = [
        'property_logbook_entry_id',
        'path',
        'original_name',
        'mime_type',
        'size',
    ];

    protected function casts(): array
    {
        return ['size' => 'integer'];
    }

    public function entry(): BelongsTo
    {
        return $this->belongsTo(PropertyLogbookEntry::class, 'property_logbook_entry_id');
    }

    public function getIsImageAttribute(): bool
    {
        return in_array($this->mime_type, [
            'image/jpeg',
            'image/png',
            'image/gif',
            'image/webp',
            'image/avif',
        ], true);
    }
}
