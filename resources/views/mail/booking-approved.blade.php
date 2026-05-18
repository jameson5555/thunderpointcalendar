<p>Your Thunderpoint booking has been updated.</p>

<p>
    Guest: {{ $guestName }}<br>
    Approved areas: {{ implode(', ', $approvedAreaNames) }}<br>
    Dates: {{ $dateRange }}<br>
    Approved by: {{ $approvedByName }}
</p>

@if ($remainingAreaNames !== [])
    <p>Still pending: {{ implode(', ', $remainingAreaNames) }}</p>
@else
    <p>All requested living areas are now approved.</p>
@endif

<p>See your booking details here: <a href="{{ $dashboardUrl }}">{{ $dashboardUrl }}</a></p>