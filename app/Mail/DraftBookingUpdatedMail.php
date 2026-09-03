<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class DraftBookingUpdatedMail extends Mailable
{
    use Queueable;

    public function __construct(
        public readonly string $guestName,
        public readonly array $previousAreaNames,
        public readonly string $previousDateRange,
        public readonly array $areaNames,
        public readonly string $dateRange,
        public readonly string $requestedByName,
        public readonly string $paymentMethod,
        public readonly ?string $paymentReference,
        public readonly string $approvalUrl,
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: sprintf('Updated Thunderpoint draft booking for %s', implode(', ', $this->areaNames)),
        );
    }

    public function content(): Content
    {
        return new Content(view: 'mail.draft-booking-updated');
    }
}
