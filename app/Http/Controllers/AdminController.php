<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class AdminController extends Controller
{
    public function __invoke(Request $request): View
    {
        abort_unless($request->user()?->isAdmin(), 403);

        $bookingGroups = Booking::query()
            ->with(['livingArea', 'creator'])
            ->orderByDesc('start_date')
            ->get()
            ->groupBy(fn (Booking $booking) => $booking->booking_group ?: (string) $booking->id)
            ->map(fn (Collection $group) => $this->bookingGroupSummary($group))
            ->values();

        return view('admin.index', [
            'livingAreas' => collect(config('thunderpoint.living_areas')),
            'bookingGroups' => $bookingGroups,
            'paymentMethods' => config('thunderpoint.payment_methods'),
        ]);
    }

    private function bookingGroupSummary(Collection $group): array
    {
        /** @var Booking $first */
        $first = $group->first();

        return [
            'group' => $first->booking_group ?: (string) $first->id,
            'areas' => $group->pluck('livingArea.name')->filter()->values(),
            'guest_name' => $first->guest_name,
            'requested_by' => $first->creator?->name,
            'start_date' => $first->start_date,
            'end_date' => $first->end_date,
            'status' => $group->contains('status', Booking::STATUS_ACTIVE) ? Booking::STATUS_ACTIVE : Booking::STATUS_DRAFT,
            'amount_cents' => $first->amount_cents,
            'payment_status' => $first->payment_status,
            'payment_method' => $first->payment_method,
            'payment_reference' => $first->payment_reference,
            'approved_at' => $first->approved_at,
        ];
    }
}