<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MaintenanceTicketCost extends Model
{
    use HasFactory;

    protected $fillable = [
        'ticket_id',
        'expense_id',
        'labor_cost',
        'material_cost',
        'advance_cost',
        'final_cost',
        'currency',
        'payer',
        'payment_rule',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'labor_cost' => 'decimal:2',
            'material_cost' => 'decimal:2',
            'advance_cost' => 'decimal:2',
            'final_cost' => 'decimal:2',
        ];
    }

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(MaintenanceTicket::class, 'ticket_id');
    }

    public function expense(): BelongsTo
    {
        return $this->belongsTo(Expense::class);
    }
}
