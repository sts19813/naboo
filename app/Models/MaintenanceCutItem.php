<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MaintenanceCutItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'maintenance_cut_id',
        'ticket_id',
        'labor_total',
        'material_total',
        'grand_total',
    ];

    protected function casts(): array
    {
        return [
            'labor_total' => 'decimal:2',
            'material_total' => 'decimal:2',
            'grand_total' => 'decimal:2',
        ];
    }

    public function cut(): BelongsTo
    {
        return $this->belongsTo(MaintenanceCut::class, 'maintenance_cut_id');
    }

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(MaintenanceTicket::class, 'ticket_id');
    }
}
