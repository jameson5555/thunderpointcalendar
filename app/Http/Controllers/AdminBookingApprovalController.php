<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class AdminBookingApprovalController extends Controller
{
    public function __invoke(Request $request, string $bookingGroup): RedirectResponse
    {
        abort_unless($request->user()?->isAdmin(), 403);

        $updated = Booking::query()
            ->where('booking_group', $bookingGroup)
            ->where('status', Booking::STATUS_DRAFT)
            ->update([
                'status' => Booking::STATUS_ACTIVE,
                'approved_by' => $request->user()->id,
                'approved_at' => now(),
            ]);

        abort_if($updated === 0, 404);

        return redirect()
            ->route('admin.index')
            ->with('status', 'Draft booking approved and moved to active.');
    }
}