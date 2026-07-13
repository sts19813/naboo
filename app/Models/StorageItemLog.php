<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StorageItemLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'storage_item_id',
        'user_id',
        'action',
        'note',
        'changes',
    ];

    protected $casts = [
        'changes' => 'array',
    ];
}
