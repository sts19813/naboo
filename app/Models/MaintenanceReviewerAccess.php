<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class MaintenanceReviewerAccess extends Model
{
    protected $fillable = ['user_id', 'range_type', 'days_before', 'days_after', 'starts_on', 'ends_on'];
    protected function casts(): array { return ['starts_on' => 'date', 'ends_on' => 'date', 'days_before' => 'integer', 'days_after' => 'integer']; }
    public function ticketDateRange(): ?array
    {
        if ($this->range_type === 'custom') {
            return $this->starts_on && $this->ends_on ? [$this->starts_on->copy()->startOfDay(), $this->ends_on->copy()->endOfDay()] : null;
        }
        return [Carbon::today()->subDays($this->days_before ?? 0), Carbon::today()->addDays($this->days_after ?? 0)->endOfDay()];
    }
}
