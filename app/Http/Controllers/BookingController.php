<?php

namespace App\Http\Controllers;

use App\Http\Requests\BookingPaymentUpdateRequest;
use App\Http\Requests\BookingStoreRequest;
use App\Models\Booking;
use App\Models\LivingArea;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class BookingController extends Controller
{
    public function store(BookingStoreRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $livingAreas = LivingArea::query()
            ->whereIn('id', $validated['living_area_ids'])
            ->orderBy('display_order')
            ->get();

        $startDate = CarbonImmutable::parse($validated['start_date']);
        $endDate = CarbonImmutable::parse($validated['end_date']);

        $this->ensureAreasAreAvailable($livingAreas, $startDate, $endDate);

        $amountCents = $this->calculateAmountCents($request->user(), $livingAreas, $startDate, $endDate);
        $paymentStatus = $this->resolvePaymentStatus(
            $validated['payment_method'] ?? 'pay_later',
            $validated['payment_reference'] ?? null,
        );
        $groupCode = (string) Str::uuid();

        DB::transaction(function () use ($request, $validated, $livingAreas, $startDate, $endDate, $amountCents, $paymentStatus, $groupCode): void {
            foreach ($livingAreas as $livingArea) {
                Booking::create([
                    'booking_group' => $groupCode,
                    'living_area_id' => $livingArea->id,
                    'created_by' => $request->user()->id,
                    'guest_name' => $validated['guest_name'],
                    'start_date' => $startDate->toDateString(),
                    'end_date' => $endDate->toDateString(),
                    'status' => Booking::STATUS_DRAFT,
                    'note' => $validated['note'] ?? null,
                    'amount_cents' => $amountCents,
                    'payment_status' => $paymentStatus,
                    'payment_method' => $validated['payment_method'] ?? 'pay_later',
                    'payment_reference' => $validated['payment_reference'] ?? null,
                ]);
            }
        });

        return redirect()
            ->route('dashboard')
            ->with('status', sprintf('Draft booking submitted for %s.', $livingAreas->pluck('name')->join(', ')));
    }

    public function updatePayment(BookingPaymentUpdateRequest $request, string $bookingGroup): RedirectResponse
    {
        $bookings = Booking::query()
            ->where('booking_group', $bookingGroup)
            ->where('created_by', $request->user()->id)
            ->get();

        abort_if($bookings->isEmpty(), 404);

        if ($bookings->contains(fn (Booking $booking) => $booking->status !== Booking::STATUS_DRAFT)) {
            throw ValidationException::withMessages([
                'payment_method' => 'Only draft bookings can update payment details.',
            ]);
        }

        $validated = $request->validated();
        $paymentMethod = $validated['payment_method'];
        $paymentReference = $validated['payment_reference'] ?? null;
        $paymentStatus = $this->resolvePaymentStatus($paymentMethod, $paymentReference);

        Booking::query()
            ->where('booking_group', $bookingGroup)
            ->update([
                'payment_method' => $paymentMethod,
                'payment_reference' => $paymentReference,
                'payment_status' => $paymentStatus,
            ]);

        return redirect()
            ->route('dashboard')
            ->with('status', 'Payment details updated for your draft booking.');
    }

    private function ensureAreasAreAvailable(Collection $livingAreas, CarbonImmutable $startDate, CarbonImmutable $endDate): void
    {
        $conflicts = Booking::query()
            ->with('livingArea')
            ->blocking()
            ->whereIn('living_area_id', $livingAreas->pluck('id'))
            ->overlapping($startDate, $endDate)
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

    private function calculateAmountCents($user, Collection $livingAreas, CarbonImmutable $startDate, CarbonImmutable $endDate): int
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

    private function resolvePaymentStatus(string $paymentMethod, ?string $paymentReference): string
    {
        if ($paymentMethod === 'pay_later') {
            return Booking::PAYMENT_UNPAID;
        }

        if (filled($paymentReference)) {
            return Booking::PAYMENT_SUBMITTED;
        }

        return Booking::PAYMENT_PENDING;
    }
}