<?php

namespace App\Http\Controllers;

use App\Http\Requests\BookingPaymentUpdateRequest;
use App\Http\Requests\BookingStoreRequest;
use App\Models\BookingActivityLog;
use App\Models\Booking;
use App\Models\LivingArea;
use App\Models\User;
use App\Services\BookingGroupService;
use App\Services\BookingNotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class BookingController extends Controller
{
    public function __construct(
        private readonly BookingGroupService $bookingGroups,
        private readonly BookingNotificationService $notifications,
    )
    {
    }

    public function store(BookingStoreRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $livingAreas = LivingArea::query()
            ->whereIn('id', $validated['living_area_ids'])
            ->orderBy('display_order')
            ->get();

        $user = $request->user();
        $bookAsDraft = (bool) ($validated['book_as_draft'] ?? false);
        $canCreateConfirmedBooking = $this->canCreateConfirmedBooking($user, $livingAreas);
        $status = $canCreateConfirmedBooking && ! $bookAsDraft
            ? Booking::STATUS_ACTIVE
            : Booking::STATUS_DRAFT;

        $createdBookings = $this->bookingGroups->create(
            $user,
            $livingAreas,
            $validated,
            $status,
            $status === Booking::STATUS_ACTIVE ? $user : null,
        );

        foreach ($createdBookings as $booking) {
            BookingActivityLog::create([
                'booking_id' => $booking->id,
                'booking_group' => $booking->booking_group,
                'actor_id' => $user->id,
                'action' => $status === Booking::STATUS_ACTIVE ? 'active_booking_created' : 'draft_submitted',
                'to_status' => $status,
                'details' => [
                    'living_area_id' => $booking->living_area_id,
                    'guest_name' => $booking->guest_name,
                    'created_from_dashboard' => true,
                ],
            ]);
        }

        if ($status === Booking::STATUS_DRAFT) {
            $this->notifications->notifyDraftSubmitted($createdBookings);
        }

        return redirect()
            ->route('dashboard')
            ->with('status', sprintf(
                $status === Booking::STATUS_ACTIVE ? 'Confirmed booking created for %s.' : 'Draft booking submitted for %s.',
                $livingAreas->pluck('name')->join(', ')
            ));
    }

    private function canCreateConfirmedBooking(User $user, Collection $livingAreas): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if (! $user->canAccessAdmin()) {
            return false;
        }

        return $livingAreas->every(fn (LivingArea $livingArea) => $user->managesArea($livingArea->id));
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
        $paymentStatus = $this->bookingGroups->resolvePaymentStatus($paymentMethod, $paymentReference);

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
}