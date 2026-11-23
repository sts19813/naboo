<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MaintenanceTicketValidation extends Model
{
    protected $fillable = ['ticket_id', 'validated_by_user_id', 'validated_at'];
    protected function casts(): array { return ['validated_at' => 'datetime']; }
    public function validatedBy() { return $this->belongsTo(User::class, 'validated_by_user_id'); }
}
