<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

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

        return $this->relationLoaded('managedAreas')
            ? $this->managedAreas->isNotEmpty()
            : false;
    }
}
