<?php

namespace App\Http\Controllers;

use App\Http\Requests\AdminActiveBookingStoreRequest;
use App\Models\Booking;
use App\Models\BookingActivityLog;
use App\Models\LivingArea;
use App\Services\BookingGroupService;
use Illuminate\Http\RedirectResponse;

class AdminActiveBookingController extends Controller
{
    public function __construct(private readonly BookingGroupService $bookingGroups)
    {
    }

    public function __invoke(AdminActiveBookingStoreRequest $request): RedirectResponse
    {
        $user = $request->user();
        $validated = $request->validated();

        $livingAreas = $user->isAdmin()
            ? LivingArea::query()->whereIn('id', $validated['living_area_ids'])->orderBy('display_order')->get()
            : $user->managedAreas()->whereIn('living_areas.id', $validated['living_area_ids'])->orderBy('display_order')->get();

        abort_if($livingAreas->count() !== count($validated['living_area_ids']), 403);

        $createdBookings = $this->bookingGroups->create($user, $livingAreas, $validated, Booking::STATUS_ACTIVE, $user);

        foreach ($createdBookings as $booking) {
            BookingActivityLog::create([
                'booking_id' => $booking->id,
                'booking_group' => $booking->booking_group,
                'actor_id' => $user->id,
                'action' => 'active_booking_created',
                'to_status' => Booking::STATUS_ACTIVE,
                'details' => [
                    'living_area_id' => $booking->living_area_id,
                    'guest_name' => $booking->guest_name,
                    'created_directly' => true,
                ],
            ]);
        }

        return redirect()
            ->route('admin.index')
            ->with('status', sprintf('Confirmed stay created for %s.', $createdBookings->pluck('livingArea.name')->join(', ')));
    }
}