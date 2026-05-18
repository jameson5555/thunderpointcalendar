<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Booking extends Model
{
    protected $fillable = [
        'living_area_id',
        'created_by',
        'approved_by',
        'guest_name',
        'start_date',
        'end_date',
        'status',
        'note',
        'amount_cents',
        'payment_status',
        'payment_method',
        'payment_reference',
        'approved_at',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'approved_at' => 'datetime',
        ];
    }

    public function livingArea(): BelongsTo
    {
        return $this->belongsTo(LivingArea::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}