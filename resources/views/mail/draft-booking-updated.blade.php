<p>A Thunderpoint draft booking was updated and needs review.</p>

<p>
    Guest: {{ $guestName }}<br>
    Requested by: {{ $requestedByName ?: 'Unknown user' }}<br>
    Living areas: {{ implode(', ', $areaNames) }}<br>
    Dates: {{ $dateRange }}<br>
    Payment method: {{ $paymentMethod }}<br>
    Payment reference: {{ $paymentReference ?: 'None yet' }}
</p>

<p>
    Previously: {{ implode(', ', $previousAreaNames) }} · {{ $previousDateRange }}
</p>

<p>Review it here: <a href="{{ $approvalUrl }}">{{ $approvalUrl }}</a></p>
