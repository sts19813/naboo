<?php

namespace App\Support;

use App\Models\Property;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class PropertyVisibility
{
    public function shouldLimitToOwnProperties(?User $user): bool
    {
        return (bool) $user
            && ! $user->hasAnyRole(['administrador', 'admin'])
            && (
                $user->hasAnyRole(['asesores', 'asesor', 'advisor'])
                || $user->can('propiedades.ver_propias')
            );
    }

    public function scopeVisibleToUser(Builder $query, ?User $user): Builder
    {
        if (! $this->shouldLimitToOwnProperties($user)) {
            return $query;
        }

        return $this->scopeOwnProperties($query, $user);
    }

    public function scopeOwnProperties(Builder $query, User $user): Builder
    {
        return $query->where(function (Builder $builder) use ($user): void {
            $builder
                ->where('created_by', $user->id)
                ->orWhere('advisor_user_id', $user->id)
                ->orWhereHas('advisors', fn (Builder $advisorQuery) => $advisorQuery->whereKey($user->id));
        });
    }

    public function canView(?User $user, Property $property): bool
    {
        if (! $this->shouldLimitToOwnProperties($user)) {
            return true;
        }

        return (int) $property->created_by === (int) $user->id
            || (int) $property->advisor_user_id === (int) $user->id
            || $property->advisors()->whereKey($user->id)->exists();
    }

    public function ownPropertyIds(User $user): Collection
    {
        return $this->scopeOwnProperties(Property::query(), $user)
            ->pluck('id')
            ->unique()
            ->values();
    }
}
