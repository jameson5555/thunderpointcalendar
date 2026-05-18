<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Collection;

#[Fillable(['name', 'email', 'password', 'site_role', 'approved_at'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'approved_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function managedAreas(): BelongsToMany
    {
        return $this->belongsToMany(LivingArea::class)
            ->withPivot('role')
            ->withTimestamps();
    }

    public function createdBookings(): HasMany
    {
        return $this->hasMany(Booking::class, 'created_by');
    }

    public function isApproved(): bool
    {
        return $this->approved_at !== null;
    }

    public function isAdmin(): bool
    {
        return $this->site_role === 'admin';
    }

    public function canAccessAdmin(): bool
    {
        if ($this->isAdmin()) {
            return true;
        }

        if ($this->relationLoaded('managedAreas')) {
            return $this->managedAreas->isNotEmpty();
        }

        return $this->managedAreas()->exists();
    }

    public function managedAreaIds(): Collection
    {
        return $this->managedAreas()
            ->pluck('living_areas.id');
    }

    public function managesArea(int $livingAreaId): bool
    {
        if ($this->isAdmin()) {
            return true;
        }

        if ($this->relationLoaded('managedAreas')) {
            return $this->managedAreas->contains('id', $livingAreaId);
        }

        return $this->managedAreas()
            ->whereKey($livingAreaId)
            ->exists();
    }

    public function managesAnyArea(iterable $livingAreaIds): bool
    {
        if ($this->isAdmin()) {
            return true;
        }

        return $this->managedAreas()
            ->whereIn('living_areas.id', collect($livingAreaIds)->all())
            ->exists();
    }
}
