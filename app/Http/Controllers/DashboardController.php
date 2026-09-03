<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\LivingArea;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $user = $request->user()->loadMissing('managedAreas');
        $month = $request->string('month')->toString();
        $currentMonth = $month !== ''
            ? CarbonImmutable::createFromFormat('Y-m', $month)->startOfMonth()
            : CarbonImmutable::now(config('app.timezone'))->startOfMonth();

        $livingAreas = LivingArea::query()
            ->orderBy('display_order')
            ->get();

        $weekdays = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];

        $gridStart = $currentMonth->startOfWeek(CarbonImmutable::SUNDAY);
        $gridEnd = $currentMonth->endOfMonth()->endOfWeek(CarbonImmutable::SATURDAY);
        $today = CarbonImmutable::now(config('app.timezone'))->startOfDay();

        $bookings = Booking::query()
            ->with('livingArea')
            ->whereIn('status', [Booking::STATUS_DRAFT, Booking::STATUS_ACTIVE])
            ->whereDate('end_date', '>=', $gridStart->toDateString())
            ->whereDate('start_date', '<=', $gridEnd->toDateString())
            ->orderBy('start_date')
            ->get();

        $activeBookings = Booking::query()
            ->where('status', Booking::STATUS_ACTIVE)
            ->orderBy('start_date')
            ->get();

        $calendarBookingGroups = $bookings
            ->groupBy(fn (Booking $booking) => $booking->booking_group ?: (string) $booking->id)
            ->map(fn (Collection $group) => $this->calendarBookingGroupSummary(
                $group,
                $user,
                $activeBookings,
            ));

        $myBookings = Booking::query()
            ->with('livingArea')
            ->where('created_by', $user->id)
            ->orderByDesc('start_date')
            ->get()
            ->groupBy(fn (Booking $booking) => $booking->booking_group ?: (string) $booking->id)
            ->map(fn (Collection $group) => $this->myBookingGroupSummary($group))
            ->values();

        $calendarDays = collect();

        for ($date = $gridStart; $date->lessThanOrEqualTo($gridEnd); $date = $date->addDay()) {
            $calendarDays->push([
                'date' => $date,
                'isCurrentMonth' => $date->month === $currentMonth->month,
                'isToday' => $date->equalTo($today),
                'bookingGroups' => $calendarBookingGroups
                    ->filter(fn (array $group) => $group['startDate'] <= $date->toDateString()
                        && $group['endDate'] >= $date->toDateString())
                    ->keys()
                    ->values()
                    ->all(),
            ]);
        }

        $calendarWeeks = $calendarDays
            ->chunk(7)
            ->values()
            ->map(function (Collection $days) use ($bookings): array {
                $weekStart = $days->first()['date'];
                $weekEnd = $days->last()['date'];
                $rangeLayout = $this->rangeLayoutForWeek($weekStart, $weekEnd, $bookings);

                return [
                    'days' => $days->values(),
                    'segments' => $rangeLayout['segments'],
                    'laneCount' => $rangeLayout['lane_count'],
                ];
            });

        return view('dashboard', [
            'canCreateConfirmedBookings' => $user->canAccessAdmin(),
            'calendarBookingGroups' => $calendarBookingGroups,
            'calendarWeeks' => $calendarWeeks,
            'currentMonth' => $currentMonth->format('Y-m'),
            'livingAreas' => $livingAreas,
            'monthLabel' => $currentMonth->format('F Y'),
            'myBookings' => $myBookings,
            'paymentMethods' => config('thunderpoint.payment_methods'),
            'unavailableDateRangesByArea' => $this->unavailableRangesByArea($activeBookings),
            'weekdays' => $weekdays,
        ]);
    }

    private function unavailableRangesByArea(Collection $bookings, ?string $exceptBookingGroup = null): array
    {
        return $bookings
            ->filter(fn (Booking $booking) => $exceptBookingGroup === null || $booking->booking_group !== $exceptBookingGroup)
            ->groupBy('living_area_id')
            ->map(fn (Collection $areaBookings) => $areaBookings
                ->sortBy('start_date')
                ->map(fn (Booking $booking) => [
                    'from' => $booking->start_date->toDateString(),
                    'to' => $booking->end_date->toDateString(),
                ])
                ->values()
                ->all())
            ->all();
    }

    private function rangeLayoutForWeek(CarbonImmutable $weekStart, CarbonImmutable $weekEnd, Collection $bookings): array
    {
        $lanes = [];

        $segments = $bookings
            ->filter(function (Booking $booking) use ($weekStart, $weekEnd): bool {
                return $booking->start_date->toDateString() <= $weekEnd->toDateString()
                    && $booking->end_date->toDateString() >= $weekStart->toDateString();
            })
            ->sortBy(fn (Booking $booking) => sprintf(
                '%s-%02d-%s',
                $booking->start_date->toDateString(),
                $booking->livingArea?->display_order ?? 0,
                $booking->guest_name,
            ))
            ->values()
            ->map(function (Booking $booking) use ($weekStart, $weekEnd, &$lanes): array {
                $segmentStart = $booking->start_date->greaterThan($weekStart) ? $booking->start_date : $weekStart;
                $segmentEnd = $booking->end_date->lessThan($weekEnd) ? $booking->end_date : $weekEnd;
                $columnStart = $weekStart->diffInDays($segmentStart) + 1;
                $columnEnd = $weekStart->diffInDays($segmentEnd) + 2;
                $lane = null;

                foreach ($lanes as $index => $occupiedThrough) {
                    if ($columnStart > $occupiedThrough) {
                        $lane = $index;
                        break;
                    }
                }

                if ($lane === null) {
                    $lane = count($lanes);
                }

                $lanes[$lane] = $columnEnd - 1;

                $deepColor = $booking->livingArea?->deep_color ?? '#4a3422';
                $softColor = $booking->livingArea?->soft_color ?? '#f7f1df';
                $baseClasses = 'truncate px-2 py-1 text-[10px] font-semibold leading-4 sm:px-2.5 sm:text-[11px]';

                if ($booking->status === Booking::STATUS_ACTIVE) {
                    return [
                        'booking_group' => $booking->booking_group ?: (string) $booking->id,
                        'label' => $booking->guest_name,
                        'area_name' => $booking->livingArea?->name ?? 'Unknown part',
                        'area_color' => $deepColor,
                        'guest_name' => $booking->guest_name,
                        'start_date' => $booking->start_date->format('M j, Y'),
                        'end_date' => $booking->end_date->format('M j, Y'),
                        'status_label' => 'Confirmed',
                        'style' => $baseClasses.' text-white',
                        'inline_style' => sprintf('background-color: %s;', $deepColor),
                        'column_start' => $columnStart,
                        'column_end' => $columnEnd,
                        'lane' => $lane + 1,
                        'title' => sprintf(
                            '%s: %s (%s to %s)',
                            $booking->livingArea?->name,
                            $booking->guest_name,
                            $booking->start_date->format('M j'),
                            $booking->end_date->format('M j'),
                        ),
                    ];
                }

                return [
                    'booking_group' => $booking->booking_group ?: (string) $booking->id,
                    'label' => sprintf('%s - DRAFT', $booking->guest_name),
                    'area_name' => $booking->livingArea?->name ?? 'Unknown part',
                    'area_color' => $deepColor,
                    'guest_name' => $booking->guest_name,
                    'start_date' => $booking->start_date->format('M j, Y'),
                    'end_date' => $booking->end_date->format('M j, Y'),
                    'status_label' => 'Draft',
                    'style' => $baseClasses.' border border-dashed',
                    'inline_style' => sprintf('border-color: %s; background-color: %s; color: %s;', $deepColor, $softColor, $deepColor),
                    'column_start' => $columnStart,
                    'column_end' => $columnEnd,
                    'lane' => $lane + 1,
                    'title' => sprintf(
                        '%s draft: %s (%s to %s)',
                        $booking->livingArea?->name,
                        $booking->guest_name,
                        $booking->start_date->format('M j'),
                        $booking->end_date->format('M j'),
                    ),
                ];
            })
            ->all();

        return [
            'segments' => $segments,
            'lane_count' => max(1, count($lanes)),
        ];
    }

    private function calendarBookingGroupSummary(Collection $group, User $user, Collection $activeBookings): array
    {
        /** @var Booking $first */
        $first = $group->first();
        $bookingGroup = $first->booking_group ?: (string) $first->id;
        $statuses = $group->pluck('status')->unique()->values();
        $groupStatus = $statuses->count() > 1 ? 'mixed' : $statuses->first();
        $isDraftOwner = $groupStatus === Booking::STATUS_DRAFT
            && $group->every(fn (Booking $booking) => $booking->created_by === $user->id);
        $managesEveryArea = $user->canAccessAdmin()
            && $group->every(fn (Booking $booking) => $user->managesArea($booking->living_area_id));
        $canEditActive = $groupStatus === Booking::STATUS_ACTIVE
            && ($user->isAdmin() || $managesEveryArea);
        $canEdit = $isDraftOwner || $canEditActive;

        $summary = [
            'group' => $bookingGroup,
            'guestName' => $first->guest_name,
            'areas' => $group
                ->sortBy(fn (Booking $booking) => $booking->livingArea?->display_order ?? 0)
                ->map(fn (Booking $booking) => [
                    'name' => $booking->livingArea?->name ?? 'Unknown part',
                    'color' => $booking->livingArea?->deep_color ?? '#4a3422',
                ])
                ->values()
                ->all(),
            'startDate' => $first->start_date->toDateString(),
            'endDate' => $first->end_date->toDateString(),
            'formattedStartDate' => $first->start_date->format('M j, Y'),
            'formattedEndDate' => $first->end_date->format('M j, Y'),
            'statusLabel' => match ($groupStatus) {
                Booking::STATUS_ACTIVE => 'Confirmed',
                Booking::STATUS_DRAFT => 'Draft',
                default => 'Mixed',
            },
            'canEdit' => $canEdit,
        ];

        if (! $canEdit) {
            return $summary;
        }

        return [
            ...$summary,
            'edit' => [
                'action' => $isDraftOwner
                    ? route('bookings.draft.update', $bookingGroup, absolute: false)
                    : route('admin.bookings.update', $bookingGroup, absolute: false),
                'areaIds' => $group->pluck('living_area_id')->map(fn (int $id) => (string) $id)->values()->all(),
                'guestName' => $first->guest_name,
                'startDate' => $first->start_date->toDateString(),
                'endDate' => $first->end_date->toDateString(),
                'note' => $first->note ?? '',
                'paymentMethod' => $first->payment_method ?? 'pay_later',
                'paymentReference' => $first->payment_reference ?? '',
                'lockAreas' => $canEditActive && ! $user->isAdmin(),
                'unavailableRangesByArea' => $this->unavailableRangesByArea(
                    $activeBookings,
                    $groupStatus === Booking::STATUS_ACTIVE ? $bookingGroup : null,
                ),
            ],
        ];
    }

    private function myBookingGroupSummary(Collection $group): array
    {
        /** @var Booking $first */
        $first = $group->first();
        $areaNames = $group->pluck('livingArea.name')->filter()->values();

        $status = $group->pluck('status')->unique()->values();
        $groupStatus = $status->count() > 1 ? 'mixed' : $status->first();

        return [
            'group' => $first->booking_group ?: (string) $first->id,
            'areas' => $areaNames,
            'guest_name' => $first->guest_name,
            'start_date' => $first->start_date,
            'end_date' => $first->end_date,
            'status' => $groupStatus,
            'amount_cents' => $first->amount_cents,
            'payment_status' => $first->payment_status,
            'payment_method' => $first->payment_method,
            'payment_reference' => $first->payment_reference,
            'note' => $first->note,
            'can_update_payment' => $group->every(fn (Booking $booking) => $booking->status === Booking::STATUS_DRAFT),
        ];
    }
}
