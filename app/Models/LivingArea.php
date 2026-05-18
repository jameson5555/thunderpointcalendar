<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Arr;

class LivingArea extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'deep_color',
        'soft_color',
        'booking_message',
        'display_order',
    ];

    public function managers(): BelongsToMany
    {
        return $this->belongsToMany(User::class)
            ->withPivot('role')
            ->withTimestamps();
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    public function description(): string
    {
        $configArea = collect(config('thunderpoint.living_areas'))
            ->firstWhere('slug', $this->slug);

        return Arr::get($configArea, 'description', $this->name);
    }
}