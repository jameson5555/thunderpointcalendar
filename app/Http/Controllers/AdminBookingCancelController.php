<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\BookingActivityLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminBookingCancelController extends Controller
{
    public function __invoke(Request $request, string $bookingGroup): RedirectResponse
    {
        $user = $request->user();

        abort_unless($user?->canAccessAdmin(), 403);

        $query = Booking::query()
            ->where('booking_group', $bookingGroup)
            ->where('status', Booking::STATUS_ACTIVE);

        if (! $user->isAdmin()) {
            $query->whereIn('living_area_id', $user->managedAreaIds());
        }

        $bookings = $query->get();

        abort_if($bookings->isEmpty(), 404);

        DB::transaction(function () use ($bookings, $user): void {
            foreach ($bookings as $booking) {
                $booking->forceFill([
                    'status' => Booking::STATUS_CANCELLED,
                    'cancelled_by' => $user->id,
                    'cancelled_at' => now(),
                ])->save();

                BookingActivityLog::create([
                    'booking_id' => $booking->id,
                    'booking_group' => $booking->booking_group,
                    'actor_id' => $user->id,
                    'action' => 'booking_cancelled',
                    'from_status' => Booking::STATUS_ACTIVE,
                    'to_status' => Booking::STATUS_CANCELLED,
                    'details' => [
                        'living_area_id' => $booking->living_area_id,
                        'guest_name' => $booking->guest_name,
                    ],
                ]);
            }
        });

        return redirect()
            ->route('admin.index')
            ->with('status', 'Confirmed stay cancelled.');
    }
}