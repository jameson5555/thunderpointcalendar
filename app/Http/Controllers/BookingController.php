<?php

namespace App\Http\Controllers;

use App\Http\Requests\BookingPaymentUpdateRequest;
use App\Http\Requests\BookingStoreRequest;
use App\Models\BookingActivityLog;
use App\Models\Booking;
use App\Models\LivingArea;
use App\Services\BookingGroupService;
use App\Services\BookingNotificationService;
use Illuminate\Http\RedirectResponse;
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

        $createdBookings = $this->bookingGroups->create($request->user(), $livingAreas, $validated, Booking::STATUS_DRAFT);

        foreach ($createdBookings as $booking) {
            BookingActivityLog::create([
                'booking_id' => $booking->id,
                'booking_group' => $booking->booking_group,
                'actor_id' => $request->user()->id,
                'action' => 'draft_submitted',
                'to_status' => Booking::STATUS_DRAFT,
                'details' => [
                    'living_area_id' => $booking->living_area_id,
                    'guest_name' => $booking->guest_name,
                ],
            ]);
        }

        $this->notifications->notifyDraftSubmitted($createdBookings);

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