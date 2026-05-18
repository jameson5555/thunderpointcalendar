<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\LivingArea;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
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
            ->whereDate('end_date', '>=', $gridStart->toDateString())
            ->whereDate('start_date', '<=', $gridEnd->toDateString())
            ->orderBy('start_date')
            ->get();

        $myBookings = Booking::query()
            ->with('livingArea')
            ->where('created_by', $request->user()->id)
            ->orderByDesc('start_date')
            ->get()
            ->groupBy(fn (Booking $booking) => $booking->booking_group ?: (string) $booking->id)
            ->map(fn (Collection $group) => $this->bookingGroupSummary($group))
            ->values();

        $calendarDays = collect();

        for ($date = $gridStart; $date->lessThanOrEqualTo($gridEnd); $date = $date->addDay()) {
            $calendarDays->push([
                'date' => $date,
                'isCurrentMonth' => $date->month === $currentMonth->month,
                'isToday' => $date->equalTo($today),
                'markers' => $this->markersForDate($date, $bookings),
            ]);
        }

        return view('dashboard', [
            'calendarDays' => $calendarDays,
            'livingAreas' => $livingAreas,
            'monthLabel' => $currentMonth->format('F Y'),
            'myBookings' => $myBookings,
            'paymentMethods' => config('thunderpoint.payment_methods'),
            'weekdays' => $weekdays,
        ]);
    }

    private function markersForDate(CarbonImmutable $date, Collection $bookings): array
    {
        $markers = $bookings
            ->filter(function (Booking $booking) use ($date): bool {
                return $booking->start_date->toDateString() <= $date->toDateString()
                    && $booking->end_date->toDateString() >= $date->toDateString();
            })
            ->sortBy(fn (Booking $booking) => $booking->livingArea?->display_order ?? 0)
            ->values()
            ->map(function (Booking $booking): array {
                $deepColor = $booking->livingArea?->deep_color ?? '#4a3422';
                $softColor = $booking->livingArea?->soft_color ?? '#f7f1df';

                if ($booking->status === Booking::STATUS_ACTIVE) {
                    return [
                        'label' => sprintf('%s: %s', $booking->livingArea?->name, $booking->guest_name),
                        'style' => 'text-white',
                        'inline_style' => sprintf('background-color: %s;', $deepColor),
                    ];
                }

                return [
                    'label' => sprintf('%s draft: %s', $booking->livingArea?->name, $booking->guest_name),
                    'style' => 'border border-dashed',
                    'inline_style' => sprintf('border-color: %s; background-color: %s; color: %s;', $deepColor, $softColor, $deepColor),
                ];
            })
            ->all();

        if (count($markers) > 3) {
            $hiddenCount = count($markers) - 2;
            $markers = array_slice($markers, 0, 2);
            $markers[] = [
                'label' => sprintf('+%d more', $hiddenCount),
                'style' => 'border border-dashed border-[rgba(61,52,39,0.18)] bg-white/80 text-[rgba(61,52,39,0.72)]',
                'inline_style' => '',
            ];
        }

        return $markers;
    }

    private function bookingGroupSummary(Collection $group): array
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