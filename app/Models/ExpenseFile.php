<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExpenseFile extends Model
{
    use HasFactory;

    public const TYPE_IMAGE = 'image';
    public const TYPE_PDF = 'pdf';

    protected $fillable = [
        'expense_id',
        'path',
        'type',
        'mime_type',
        'original_name',
        'size',
    ];

    protected function casts(): array
    {
        return [
            'size' => 'integer',
        ];
    }

    public function expense(): BelongsTo
    {
        return $this->belongsTo(Expense::class);
    }

    public function getIsImageAttribute(): bool
    {
        return $this->type === self::TYPE_IMAGE;
    }

    public function getIsPdfAttribute(): bool
    {
        return $this->type === self::TYPE_PDF;
    }
}
