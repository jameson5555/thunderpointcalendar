<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Booking extends Model
{
    public const STATUS_DRAFT = 'draft';

    public const STATUS_ACTIVE = 'active';

    public const PAYMENT_UNPAID = 'unpaid';

    public const PAYMENT_PENDING = 'pending';

    public const PAYMENT_SUBMITTED = 'submitted';

    protected $fillable = [
        'booking_group',
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

    public function scopeBlocking(Builder $query): Builder
    {
        return $query->whereIn('status', [self::STATUS_DRAFT, self::STATUS_ACTIVE]);
    }

    public function scopeOverlapping(Builder $query, CarbonInterface|string $startDate, CarbonInterface|string $endDate): Builder
    {
        $start = $startDate instanceof CarbonInterface ? $startDate->toDateString() : $startDate;
        $end = $endDate instanceof CarbonInterface ? $endDate->toDateString() : $endDate;

        return $query
            ->whereDate('start_date', '<=', $end)
            ->whereDate('end_date', '>=', $start);
    }

    public function nights(): int
    {
        return $this->start_date->diffInDays($this->end_date) + 1;
    }
}