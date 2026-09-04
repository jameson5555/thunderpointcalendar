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

    public function updateDraft(User $user, string $bookingGroup, Collection $livingAreas, array $attributes): Collection
    {
        $startDate = CarbonImmutable::parse($attributes['start_date']);
        $endDate = CarbonImmutable::parse($attributes['end_date']);

        $this->ensureAreasAreAvailable($livingAreas, $startDate, $endDate);

        $amountCents = $this->calculateAmountCents($user, $livingAreas, $startDate, $endDate);
        $paymentMethod = $attributes['payment_method'] ?? 'pay_later';
        $paymentReference = $attributes['payment_reference'] ?? null;
        $paymentStatus = $this->resolvePaymentStatus($paymentMethod, $paymentReference);

        DB::transaction(function () use ($user, $bookingGroup, $livingAreas, $attributes, $startDate, $endDate, $amountCents, $paymentMethod, $paymentReference, $paymentStatus): void {
            $existingBookings = Booking::query()
                ->where('booking_group', $bookingGroup)
                ->lockForUpdate()
                ->get()
                ->keyBy('living_area_id');

            abort_if($existingBookings->isEmpty(), 404);
            abort_unless(
                $existingBookings->every(fn (Booking $booking) => $booking->status === Booking::STATUS_DRAFT
                    && $booking->created_by === $user->id),
                403,
            );

            $selectedAreaIds = $livingAreas->pluck('id');

            foreach ($livingAreas as $livingArea) {
                $values = [
                    'created_by' => $user->id,
                    'guest_name' => $attributes['guest_name'],
                    'start_date' => $startDate->toDateString(),
                    'end_date' => $endDate->toDateString(),
                    'status' => Booking::STATUS_DRAFT,
                    'note' => $attributes['note'] ?? null,
                    'amount_cents' => $amountCents,
                    'payment_status' => $paymentStatus,
                    'payment_method' => $paymentMethod,
                    'payment_reference' => $paymentReference,
                    'approved_by' => null,
                    'approved_at' => null,
                ];

                $existingBooking = $existingBookings->get($livingArea->id);

                if ($existingBooking) {
                    $existingBooking->forceFill($values)->save();
                    continue;
                }

                Booking::create([
                    'booking_group' => $bookingGroup,
                    'living_area_id' => $livingArea->id,
                    ...$values,
                ]);
            }

            Booking::query()
                ->where('booking_group', $bookingGroup)
                ->whereNotIn('living_area_id', $selectedAreaIds)
                ->delete();
        });

        return Booking::query()
            ->with(['livingArea', 'creator'])
            ->where('booking_group', $bookingGroup)
            ->where('status', Booking::STATUS_DRAFT)
            ->orderBy('living_area_id')
            ->get();
    }

    public function updateActive(User $actor, string $bookingGroup, Collection $livingAreas, array $attributes): Collection
    {
        $startDate = CarbonImmutable::parse($attributes['start_date']);
        $endDate = CarbonImmutable::parse($attributes['end_date']);

        $this->ensureAreasAreAvailable($livingAreas, $startDate, $endDate, $bookingGroup);

        DB::transaction(function () use ($actor, $bookingGroup, $livingAreas, $attributes, $startDate, $endDate): void {
            $existingBookings = Booking::query()
                ->where('booking_group', $bookingGroup)
                ->lockForUpdate()
                ->get()
                ->keyBy('living_area_id');

            abort_if($existingBookings->isEmpty(), 404);
            abort_unless(
                $existingBookings->every(fn (Booking $booking) => $booking->status === Booking::STATUS_ACTIVE),
                403,
            );

            $originalBooking = $existingBookings->first();
            $creator = User::query()->findOrFail($originalBooking->created_by);
            $amountCents = $this->calculateAmountCents($creator, $livingAreas, $startDate, $endDate);
            $paymentMethod = $attributes['payment_method'] ?? 'pay_later';
            $paymentReference = $attributes['payment_reference'] ?? null;
            $paymentStatus = $this->resolvePaymentStatus($paymentMethod, $paymentReference);
            $selectedAreaIds = $livingAreas->pluck('id');

            foreach ($livingAreas as $livingArea) {
                $values = [
                    'guest_name' => $attributes['guest_name'],
                    'start_date' => $startDate->toDateString(),
                    'end_date' => $endDate->toDateString(),
                    'status' => Booking::STATUS_ACTIVE,
                    'note' => $attributes['note'] ?? null,
                    'amount_cents' => $amountCents,
                    'payment_status' => $paymentStatus,
                    'payment_method' => $paymentMethod,
                    'payment_reference' => $paymentReference,
                    'cancelled_by' => null,
                    'cancelled_at' => null,
                ];

                $existingBooking = $existingBookings->get($livingArea->id);

                if ($existingBooking) {
                    $existingBooking->forceFill($values)->save();
                    continue;
                }

                Booking::create([
                    'booking_group' => $bookingGroup,
                    'living_area_id' => $livingArea->id,
                    'created_by' => $originalBooking->created_by,
                    'approved_by' => $originalBooking->approved_by ?? $actor->id,
                    'approved_at' => $originalBooking->approved_at ?? now(),
                    ...$values,
                ]);
            }

            Booking::query()
                ->where('booking_group', $bookingGroup)
                ->whereNotIn('living_area_id', $selectedAreaIds)
                ->delete();
        });

        return Booking::query()
            ->with(['livingArea', 'creator', 'approver'])
            ->where('booking_group', $bookingGroup)
            ->where('status', Booking::STATUS_ACTIVE)
            ->orderBy('living_area_id')
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
