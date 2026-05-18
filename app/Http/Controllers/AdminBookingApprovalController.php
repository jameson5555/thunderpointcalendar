<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class AdminBookingApprovalController extends Controller
{
    public function __invoke(Request $request, string $bookingGroup): RedirectResponse
    {
        $user = $request->user();

        abort_unless($user?->canAccessAdmin(), 403);

        $accessibleAreaIds = $user->isAdmin()
            ? Booking::query()->where('booking_group', $bookingGroup)->pluck('living_area_id')
            : $user->managedAreaIds();

        abort_if($accessibleAreaIds->isEmpty(), 403);

        $updated = Booking::query()
            ->where('booking_group', $bookingGroup)
            ->whereIn('living_area_id', $accessibleAreaIds)
            ->where('status', Booking::STATUS_DRAFT)
            ->update([
                'status' => Booking::STATUS_ACTIVE,
                'approved_by' => $user->id,
                'approved_at' => now(),
            ]);

        abort_if($updated === 0, 403);

        return redirect()
            ->route('admin.index')
            ->with('status', 'Draft booking approved for the living areas you manage.');
    }
}