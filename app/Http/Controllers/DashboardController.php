<?php

namespace App\Http\Controllers;

use Carbon\CarbonImmutable;
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

        $livingAreas = collect(config('thunderpoint.living_areas'));
        $weekdays = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];

        $gridStart = $currentMonth->startOfWeek(CarbonImmutable::SUNDAY);
        $gridEnd = $currentMonth->endOfMonth()->endOfWeek(CarbonImmutable::SATURDAY);
        $today = CarbonImmutable::now(config('app.timezone'))->startOfDay();

        $calendarDays = collect();

        for ($date = $gridStart; $date->lessThanOrEqualTo($gridEnd); $date = $date->addDay()) {
            $calendarDays->push([
                'date' => $date,
                'isCurrentMonth' => $date->month === $currentMonth->month,
                'isToday' => $date->equalTo($today),
                'markers' => $this->markersForDate($date),
            ]);
        }

        return view('dashboard', [
            'calendarDays' => $calendarDays,
            'livingAreas' => $livingAreas,
            'monthLabel' => $currentMonth->format('F Y'),
            'weekdays' => $weekdays,
        ]);
    }

    private function markersForDate(CarbonImmutable $date): array
    {
        if ($date->isWeekend()) {
            return [[
                'label' => 'Popular arrival window',
                'style' => 'rounded-2xl border border-dashed border-[rgba(61,52,39,0.18)] bg-white/80 text-[rgba(61,52,39,0.72)]',
            ]];
        }

        return [];
    }
}