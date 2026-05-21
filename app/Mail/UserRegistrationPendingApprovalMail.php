<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class UserRegistrationPendingApprovalMail extends Mailable
{
    use Queueable;

    public function __construct(
        public readonly string $pendingUserName,
        public readonly string $pendingUserEmail,
        public readonly string $registeredAt,
        public readonly string $adminUrl,
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'New Thunderpoint user awaiting approval',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.user-registration-pending-approval',
        );
    }
}