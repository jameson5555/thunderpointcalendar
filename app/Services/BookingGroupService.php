<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\LivingArea;
use App\Models\User;
use Carbon\CarbonInterface;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class BookingGroupService
{
    public function create(User $user, Collection $livingAreas, array $attributes, string $status = Booking::STATUS_DRAFT, ?User $approvedBy = null): Collection
    {
        $startDate = CarbonImmutable::parse($attributes['start_date']);
        $endDate = CarbonImmutable::parse($attributes['end_date']);

        $this->ensureAreasAreAvailable($livingAreas, $startDate, $endDate);

        $amountCents = $this->calculateAmountCents($user, $livingAreas, $startDate, $endDate);
        $paymentMethod = $attributes['payment_method'] ?? 'pay_later';
        $paymentReference = $attributes['payment_reference'] ?? null;
        $paymentStatus = $this->resolvePaymentStatus($paymentMethod, $paymentReference);
        $groupCode = (string) Str::uuid();
        $createdBookingIds = [];

        DB::transaction(function () use ($user, $livingAreas, $attributes, $startDate, $endDate, $amountCents, $paymentMethod, $paymentReference, $paymentStatus, $groupCode, $status, $approvedBy, &$createdBookingIds): void {
            foreach ($livingAreas as $livingArea) {
                $booking = Booking::create([
                    'booking_group' => $groupCode,
                    'living_area_id' => $livingArea->id,
                    'created_by' => $user->id,
                    'guest_name' => $attributes['guest_name'],
                    'start_date' => $startDate->toDateString(),
                    'end_date' => $endDate->toDateString(),
                    'status' => $status,
                    'note' => $attributes['note'] ?? null,
                    'amount_cents' => $amountCents,
                    'payment_status' => $paymentStatus,
                    'payment_method' => $paymentMethod,
                    'payment_reference' => $paymentReference,
                    'approved_by' => $status === Booking::STATUS_ACTIVE ? $approvedBy?->id : null,
                    'approved_at' => $status === Booking::STATUS_ACTIVE ? now() : null,
                ]);

                $createdBookingIds[] = $booking->id;
            }
        });

        return Booking::query()
            ->with(['livingArea', 'creator', 'approver'])
            ->whereKey($createdBookingIds)
            ->get();
    }

    public function resolvePaymentStatus(string $paymentMethod, ?string $paymentReference): string
    {
        if ($paymentMethod === 'pay_later') {
            return Booking::PAYMENT_UNPAID;
        }

        if (filled($paymentReference)) {
            return Booking::PAYMENT_SUBMITTED;
        }

        return Booking::PAYMENT_PENDING;
    }

    public function ensureAreasAreAvailable(
        Collection $livingAreas,
        CarbonInterface|string $startDate,
        CarbonInterface|string $endDate,
        ?string $exceptBookingGroup = null,
    ): void
    {
        $conflictQuery = Booking::query()
            ->with('livingArea')
            ->blocking()
            ->whereIn('living_area_id', $livingAreas->pluck('id'))
            ->overlapping($startDate, $endDate);

        if ($exceptBookingGroup !== null) {
            $conflictQuery->where('booking_group', '!=', $exceptBookingGroup);
        }

        $conflicts = $conflictQuery
            ->get()
            ->pluck('livingArea.name')
            ->filter()
            ->unique()
            ->values();

        if ($conflicts->isNotEmpty()) {
            throw ValidationException::withMessages([
                'living_area_ids' => sprintf('These living areas already have a blocking booking in that range: %s.', $conflicts->join(', ')),
            ]);
        }
    }

    private function calculateAmountCents(User $user, Collection $livingAreas, CarbonImmutable $startDate, CarbonImmutable $endDate): int
    {
        $nights = (int) $startDate->startOfDay()->diffInDays($endDate->startOfDay()) + 1;
        $allAreaCount = LivingArea::query()->count();

        if ($livingAreas->count() === $allAreaCount && $nights === 7) {
            return 50000;
        }

        return $livingAreas
            ->sum(function (LivingArea $livingArea) use ($user, $nights): int {
                $nightlyRate = $user->managesArea($livingArea->id) ? 1000 : 2000;

                return $nightlyRate * $nights;
            });
    }

}