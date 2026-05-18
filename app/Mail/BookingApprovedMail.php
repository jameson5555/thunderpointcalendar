<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class BookingApprovedMail extends Mailable
{
    use Queueable;

    public function __construct(
        public readonly string $guestName,
        public readonly array $approvedAreaNames,
        public readonly array $remainingAreaNames,
        public readonly string $dateRange,
        public readonly string $approvedByName,
        public readonly string $dashboardUrl,
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: sprintf('Thunderpoint booking update for %s', $this->guestName),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.booking-approved',
        );
    }
}