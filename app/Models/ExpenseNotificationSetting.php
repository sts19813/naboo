<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExpenseNotificationSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'days_before',
        'emails',
        'phones',
    ];

    protected function casts(): array
    {
        return [
            'days_before' => 'integer',
            'emails' => 'array',
            'phones' => 'array',
        ];
    }

    public static function current(): self
    {
        return static::query()->firstOrCreate([], [
            'days_before' => 3,
            'emails' => [],
            'phones' => [],
        ]);
    }
}
