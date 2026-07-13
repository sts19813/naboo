<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MaintenanceTicketMessage extends Model
{
    use HasFactory;

    public const CHANNEL_LABELS = [
        'interno' => 'Interno',
        'admin_tecnico' => 'Administración - Técnico',
        'inquilino_admin' => 'Inquilino - Administración',
    ];

    protected $fillable = [
        'ticket_id',
        'sender_user_id',
        'recipient_user_id',
        'channel',
        'message',
    ];

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(MaintenanceTicket::class, 'ticket_id');
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_user_id');
    }

    public function recipient(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recipient_user_id');
    }
}
