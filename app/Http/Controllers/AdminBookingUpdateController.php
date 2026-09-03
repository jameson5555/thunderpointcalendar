<?php

namespace App\Http\Controllers;

use App\Http\Requests\AdminBookingUpdateRequest;
use App\Models\Booking;
use App\Models\BookingActivityLog;
use App\Models\LivingArea;
use App\Services\BookingGroupService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;

class AdminBookingUpdateController extends Controller
{
    public function __construct(private readonly BookingGroupService $bookingGroups)
    {
    }

    public function __invoke(AdminBookingUpdateRequest $request, string $bookingGroup): RedirectResponse
    {
        $user = $request->user();
        $validated = $request->validated();

        $groupBookings = Booking::query()
            ->where('booking_group', $bookingGroup)
            ->get();

        abort_if($groupBookings->isEmpty(), 404);
        abort_unless($groupBookings->every(fn (Booking $booking) => $booking->status === Booking::STATUS_ACTIVE), 403);
        $existingBookings = $groupBookings;

        $accessibleAreas = $user->isAdmin()
            ? LivingArea::query()->whereIn('id', $validated['living_area_ids'])->orderBy('display_order')->get()
            : $user->managedAreas()->whereIn('living_areas.id', $validated['living_area_ids'])->orderBy('display_order')->get();

        abort_if($accessibleAreas->count() !== count($validated['living_area_ids']), 403);

        if (! $user->isAdmin()) {
            $existingAreaIds = $existingBookings->pluck('living_area_id')->unique()->sort()->values()->all();
            $requestedAreaIds = $accessibleAreas->pluck('id')->unique()->sort()->values()->all();

            abort_if($existingAreaIds !== $requestedAreaIds, 403);
        }

        DB::transaction(function () use ($existingBookings, $user): void {
            foreach ($existingBookings as $booking) {
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
                        'reason' => 'replaced_by_edit',
                        'living_area_id' => $booking->living_area_id,
                    ],
                ]);
            }
        });

        $updatedBookings = $this->bookingGroups->create($user, $accessibleAreas, $validated, Booking::STATUS_ACTIVE, $user);

        foreach ($updatedBookings as $booking) {
            BookingActivityLog::create([
                'booking_id' => $booking->id,
                'booking_group' => $booking->booking_group,
                'actor_id' => $user->id,
                'action' => 'booking_updated',
                'to_status' => Booking::STATUS_ACTIVE,
                'details' => [
                    'previous_group' => $bookingGroup,
                    'living_area_id' => $booking->living_area_id,
                    'guest_name' => $booking->guest_name,
                ],
            ]);
        }

        return redirect()
            ->route(
                ($validated['form_context'] ?? null) === 'calendar-edit' ? 'dashboard' : 'admin.index',
                ($validated['form_context'] ?? null) === 'calendar-edit'
                    ? array_filter(['month' => $validated['return_month'] ?? null])
                    : [],
            )
            ->with('status', sprintf('Confirmed stay updated for %s.', $updatedBookings->pluck('livingArea.name')->join(', ')));
    }
}
