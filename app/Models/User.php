<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Notifications\ResetPasswordNotification;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */

    protected $fillable = [
        'name',
        'email',
        'password',
        'is_active',
        'profile_photo',
        'google_id',
        'google_access_token',
        'google_refresh_token',
        'google_token_expires_at',
    ];
    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'google_token_expires_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new ResetPasswordNotification($token));
    }

    public function createdProperties(): HasMany
    {
        return $this->hasMany(Property::class, 'created_by');
    }

    public function createdCharges(): HasMany
    {
        return $this->hasMany(Charge::class, 'created_by');
    }

    public function maintenanceTicketsReported(): HasMany
    {
        return $this->hasMany(MaintenanceTicket::class, 'reported_by_user_id');
    }

    public function assignedProperties(): HasMany
    {
        return $this->hasMany(Property::class, 'advisor_user_id');
    }

    public function advisorProperties(): BelongsToMany
    {
        return $this->belongsToMany(Property::class, 'property_advisor')
            ->withTimestamps();
    }

    public function hasSystemAccess(): bool
    {
        if (!$this->is_active) {
            return false;
        }

        return $this->roles()->exists() || $this->permissions()->exists();
    }

    public function profilePhotoUrl(): string
    {
        if (!$this->profile_photo) {
            return asset('metronic/assets/media/svg/avatars/blank.svg');
        }

        if (
            str_starts_with($this->profile_photo, 'http://')
            || str_starts_with($this->profile_photo, 'https://')
            || str_starts_with($this->profile_photo, '/storage/')
        ) {
            return $this->profile_photo;
        }

        if (Storage::disk('public')->exists($this->profile_photo)) {
            return Storage::url($this->profile_photo);
        }

        return asset($this->profile_photo);
    }
}
