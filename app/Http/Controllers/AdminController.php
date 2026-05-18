<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\LivingArea;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class AdminController extends Controller
{
    public function __invoke(Request $request): View
    {
        abort_unless($request->user()?->canAccessAdmin(), 403);

        $user = $request->user();
        $accessibleAreaIds = $user->isAdmin()
            ? null
            : $user->managedAreaIds();

        $livingAreas = $user->isAdmin()
            ? LivingArea::query()->with('managers')->orderBy('display_order')->get()
            : $user->managedAreas()->with('managers')->orderBy('display_order')->get();

        $bookingQuery = Booking::query()
            ->with(['livingArea', 'creator'])
            ->orderByDesc('start_date');

        if ($accessibleAreaIds !== null) {
            $bookingQuery->whereIn('living_area_id', $accessibleAreaIds);
        }

        $bookingGroups = $bookingQuery
            ->get()
            ->groupBy(fn (Booking $booking) => $booking->booking_group ?: (string) $booking->id)
            ->map(fn (Collection $group) => $this->bookingGroupSummary($group))
            ->values();

        return view('admin.index', [
            'isAdminView' => $user->isAdmin(),
            'livingAreas' => $livingAreas,
            'bookingGroups' => $bookingGroups,
            'paymentMethods' => config('thunderpoint.payment_methods'),
            'users' => $user->isAdmin()
                ? User::query()->with('managedAreas')->orderBy('name')->get()
                : collect(),
        ]);
    }

    private function bookingGroupSummary(Collection $group): array
    {
        /** @var Booking $first */
        $first = $group->first();
        $status = $group->pluck('status')->unique()->values();
        $groupStatus = $status->count() > 1 ? 'mixed' : $status->first();

        return [
            'group' => $first->booking_group ?: (string) $first->id,
            'areas' => $group->pluck('livingArea.name')->filter()->values(),
            'guest_name' => $first->guest_name,
            'requested_by' => $first->creator?->name,
            'start_date' => $first->start_date,
            'end_date' => $first->end_date,
            'status' => $groupStatus,
            'amount_cents' => $first->amount_cents,
            'payment_status' => $first->payment_status,
            'payment_method' => $first->payment_method,
            'payment_reference' => $first->payment_reference,
            'approved_at' => $first->approved_at,
        ];
    }
}