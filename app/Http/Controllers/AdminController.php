<?php

namespace App\Http\Controllers;

use App\Models\BookingActivityLog;
use App\Models\Booking;
use App\Models\LivingArea;
use App\Models\NotificationLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
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
            'recentActivity' => $this->recentActivity($accessibleAreaIds),
            'recentNotifications' => $this->recentNotifications($accessibleAreaIds),
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
            'area_ids' => $group->pluck('living_area_id')->values(),
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
            'cancelled_at' => $group->pluck('cancelled_at')->filter()->sortDesc()->first(),
            'note' => $first->note,
            'history' => $this->groupHistory($first->booking_group ?: (string) $first->id),
        ];
    }

    private function groupHistory(string $bookingGroup): array
    {
        $activity = BookingActivityLog::query()
            ->with(['actor', 'booking.livingArea'])
            ->where('booking_group', $bookingGroup)
            ->latest()
            ->get()
            ->map(function (BookingActivityLog $log): array {
                $areaName = $log->booking?->livingArea?->name ?? 'Selected area';

                return [
                    'headline' => match ($log->action) {
                        'draft_submitted' => sprintf('Draft submitted for %s', $areaName),
                        'booking_approved' => sprintf('Approved for %s', $areaName),
                        'active_booking_created' => sprintf('Confirmed stay created for %s', $areaName),
                        'booking_updated' => sprintf('Confirmed stay updated for %s', $areaName),
                        'booking_cancelled' => sprintf('Cancelled for %s', $areaName),
                        default => Str::headline(str_replace('_', ' ', $log->action)),
                    },
                    'context' => collect([
                        optional($log->created_at)->format('M j, g:i a'),
                        $log->actor?->name ? sprintf('by %s', $log->actor->name) : null,
                    ])->filter()->join(' · '),
                ];
            })
            ->all();

        $notifications = NotificationLog::query()
            ->where('booking_group', $bookingGroup)
            ->latest()
            ->get()
            ->map(function (NotificationLog $log): array {
                return [
                    'headline' => $log->subject,
                    'context' => collect([
                        sprintf('To %s', $log->recipient_name ?: $log->recipient_email),
                        optional($log->sent_at ?? $log->created_at)->format('M j, g:i a'),
                    ])->filter()->join(' · '),
                ];
            })
            ->all();

        return [
            'activity' => $activity,
            'notifications' => $notifications,
        ];
    }

    private function recentActivity(?Collection $accessibleAreaIds): Collection
    {
        $query = BookingActivityLog::query()
            ->with(['actor', 'booking.livingArea'])
            ->latest();

        if ($accessibleAreaIds !== null) {
            $query->whereIn('booking_id', Booking::query()->select('id')->whereIn('living_area_id', $accessibleAreaIds));
        }

        return $query->limit(8)->get()->map(function (BookingActivityLog $log): array {
            $areaName = $log->booking?->livingArea?->name ?? 'Selected area';
            $guestName = $log->booking?->guest_name ?? 'Booking';

            $headline = match ($log->action) {
                'draft_submitted' => sprintf('%s submitted for %s', $guestName, $areaName),
                'booking_approved' => sprintf('%s approved for %s', $areaName, $guestName),
                'active_booking_created' => sprintf('%s confirmed for %s', $guestName, $areaName),
                default => Str::headline(str_replace('_', ' ', $log->action)),
            };

            return [
                'booking_group' => $log->booking_group,
                'headline' => $headline,
                'context' => collect([
                    optional($log->created_at)->format('M j, g:i a'),
                    $log->actor?->name ? sprintf('by %s', $log->actor->name) : null,
                ])->filter()->join(' · '),
            ];
        });
    }

    private function recentNotifications(?Collection $accessibleAreaIds): Collection
    {
        $query = NotificationLog::query()->latest();

        if ($accessibleAreaIds !== null) {
            $query->whereIn('booking_group', Booking::query()->select('booking_group')->whereIn('living_area_id', $accessibleAreaIds)->distinct());
        }

        return $query->limit(8)->get()->map(function (NotificationLog $log): array {
            return [
                'booking_group' => $log->booking_group,
                'headline' => $log->subject,
                'context' => collect([
                    sprintf('To %s', $log->recipient_name ?: $log->recipient_email),
                    optional($log->sent_at ?? $log->created_at)->format('M j, g:i a'),
                ])->filter()->join(' · '),
            ];
        });
    }
}