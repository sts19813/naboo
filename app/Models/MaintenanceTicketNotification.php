<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MaintenanceTicketNotification extends Model
{
    use HasFactory;

    protected $fillable = [
        'ticket_id',
        'event',
        'channel',
        'recipient',
        'was_sent',
        'notified_at',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'was_sent' => 'boolean',
            'notified_at' => 'datetime',
            'meta' => 'array',
        ];
    }

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(MaintenanceTicket::class, 'ticket_id');
    }
}
