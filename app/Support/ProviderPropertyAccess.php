<?php
namespace App\Support;

use App\Models\Property;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class ProviderPropertyAccess
{
    public function scopeAssigned(Builder $query, User $user): Builder
    {
        return $query->whereNull('archived_at')->whereHas('supplierProviders', fn (Builder $providers) => $this->scopeIdentity($providers, $user));
    }

    public function scopeIdentity(Builder $query, User $user): Builder
    {
        return $query->where('maintenance_providers.type', 'proveedor')->where('maintenance_providers.is_active', true)
            ->where(function (Builder $identity) use ($user): void {
                $identity->where('maintenance_providers.user_id', $user->id);
                if (filled($user->email)) {
                    $identity->orWhere(fn (Builder $unlinked) => $unlinked->whereNull('maintenance_providers.user_id')->where('maintenance_providers.email', $user->email));
                }
            });
    }

    public function canView(User $user, Property $property): bool
    {
        return $this->scopeAssigned(Property::query(), $user)->whereKey($property->id)->exists();
    }
}
