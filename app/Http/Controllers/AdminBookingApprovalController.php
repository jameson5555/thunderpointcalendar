<?php

namespace App\Http\Controllers;

use App\Models\BookingActivityLog;
use App\Models\Booking;
use App\Services\BookingGroupService;
use App\Services\BookingNotificationService;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminBookingApprovalController extends Controller
{
    public function __construct(
        private readonly BookingNotificationService $notifications,
        private readonly BookingGroupService $bookingGroups,
    )
    {
    }

    public function __invoke(Request $request, string $bookingGroup): RedirectResponse
    {
        $user = $request->user();

        abort_unless($user?->canAccessAdmin(), 403);

        $accessibleAreaIds = $user->isAdmin()
            ? Booking::query()->where('booking_group', $bookingGroup)->pluck('living_area_id')
            : $user->managedAreaIds();

        abort_if($accessibleAreaIds->isEmpty(), 403);

        $approvedBookings = DB::transaction(function () use ($bookingGroup, $accessibleAreaIds, $user) {
            $bookings = Booking::query()
                ->with(['livingArea', 'creator'])
                ->where('booking_group', $bookingGroup)
                ->whereIn('living_area_id', $accessibleAreaIds)
                ->where('status', Booking::STATUS_DRAFT)
                ->get();

            abort_if($bookings->isEmpty(), 403);

            $this->bookingGroups->ensureAreasAreAvailable(
                $bookings->pluck('livingArea')->filter(),
                CarbonImmutable::parse($bookings->first()->start_date),
                CarbonImmutable::parse($bookings->first()->end_date),
            );

            $approvedAt = now();

            foreach ($bookings as $booking) {
                $fromStatus = $booking->status;

                $booking->forceFill([
                    'status' => Booking::STATUS_ACTIVE,
                    'approved_by' => $user->id,
                    'approved_at' => $approvedAt,
                ])->save();

                BookingActivityLog::create([
                    'booking_id' => $booking->id,
                    'booking_group' => $booking->booking_group,
                    'actor_id' => $user->id,
                    'action' => 'booking_approved',
                    'from_status' => $fromStatus,
                    'to_status' => Booking::STATUS_ACTIVE,
                    'details' => [
                        'living_area_id' => $booking->living_area_id,
                        'approved_by' => $user->name,
                    ],
                ]);
            }

            return $bookings;
        });

        $this->notifications->notifyApproval($approvedBookings, $user);

        return redirect()
            ->route('admin.index')
            ->with('status', $user->isAdmin()
                ? 'Draft booking approved.'
                : 'Draft booking approved for the living areas you manage.');
    }
}