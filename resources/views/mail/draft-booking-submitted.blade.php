<p>A new Thunderpoint draft booking was submitted and needs review.</p>

<p>
    Guest: {{ $guestName }}<br>
    Requested by: {{ $requestedByName ?: 'Unknown user' }}<br>
    Living areas: {{ implode(', ', $areaNames) }}<br>
    Dates: {{ $dateRange }}<br>
    Payment method: {{ $paymentMethod }}<br>
    Payment reference: {{ $paymentReference ?: 'None yet' }}
</p>

<p>Review it here: <a href="{{ $approvalUrl }}">{{ $approvalUrl }}</a></p>