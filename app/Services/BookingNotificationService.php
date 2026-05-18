<?php

namespace App\Services;

use App\Mail\BookingApprovedMail;
use App\Mail\DraftBookingSubmittedMail;
use App\Models\Booking;
use App\Models\NotificationLog;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Mail;

class BookingNotificationService
{
    public function notifyDraftSubmitted(Collection $bookings): void
    {
        if ($bookings->isEmpty()) {
            return;
        }

        $bookings->loadMissing(['livingArea', 'creator']);

        $firstBooking = $bookings->first();
        $areaNames = $bookings->pluck('livingArea.name')->filter()->unique()->values();
        $approvalUrl = route('admin.index', absolute: true).'#booking-group-'.$firstBooking->booking_group;
        $paymentMethod = $this->paymentMethodLabel((string) $firstBooking->payment_method);
        $subject = sprintf('New Thunderpoint draft booking for %s', $areaNames->join(', '));

        $recipients = User::query()
            ->whereNotNull('approved_at')
            ->where(function ($query) use ($bookings): void {
                $query->where('site_role', 'admin')
                    ->orWhereHas('managedAreas', function ($managedAreas) use ($bookings): void {
                        $managedAreas->whereIn('living_areas.id', $bookings->pluck('living_area_id')->all());
                    });
            })
            ->get()
            ->unique('email')
            ->values();

        foreach ($recipients as $recipient) {
            Mail::to($recipient)->send(new DraftBookingSubmittedMail(
                guestName: (string) $firstBooking->guest_name,
                areaNames: $areaNames->all(),
                dateRange: $this->formatDateRange($firstBooking),
                requestedByName: (string) optional($firstBooking->creator)->name,
                paymentMethod: $paymentMethod,
                paymentReference: $firstBooking->payment_reference,
                approvalUrl: $approvalUrl,
            ));

            NotificationLog::create([
                'booking_id' => $firstBooking->id,
                'booking_group' => $firstBooking->booking_group,
                'user_id' => $recipient->id,
                'notification_type' => 'draft_submitted',
                'recipient_email' => $recipient->email,
                'recipient_name' => $recipient->name,
                'subject' => $subject,
                'meta' => [
                    'area_names' => $areaNames->all(),
                    'approval_url' => $approvalUrl,
                ],
                'sent_at' => now(),
            ]);
        }
    }

    public function notifyApproval(Collection $approvedBookings, User $approver): void
    {
        if ($approvedBookings->isEmpty()) {
            return;
        }

        $approvedBookings->loadMissing(['livingArea', 'creator']);

        $firstBooking = $approvedBookings->first();
        $creator = $firstBooking->creator;

        if (! $creator) {
            return;
        }

        $groupBookings = Booking::query()
            ->with('livingArea')
            ->where('booking_group', $firstBooking->booking_group)
            ->get();

        $approvedAreaNames = $approvedBookings->pluck('livingArea.name')->filter()->unique()->values();
        $remainingAreaNames = $groupBookings
            ->where('status', Booking::STATUS_DRAFT)
            ->pluck('livingArea.name')
            ->filter()
            ->unique()
            ->values();
        $dashboardUrl = route('dashboard', absolute: true);
        $subject = sprintf('Thunderpoint booking update for %s', $firstBooking->guest_name);

        Mail::to($creator)->send(new BookingApprovedMail(
            guestName: (string) $firstBooking->guest_name,
            approvedAreaNames: $approvedAreaNames->all(),
            remainingAreaNames: $remainingAreaNames->all(),
            dateRange: $this->formatDateRange($firstBooking),
            approvedByName: $approver->name,
            dashboardUrl: $dashboardUrl,
        ));

        NotificationLog::create([
            'booking_id' => $firstBooking->id,
            'booking_group' => $firstBooking->booking_group,
            'user_id' => $creator->id,
            'notification_type' => 'booking_approved',
            'recipient_email' => $creator->email,
            'recipient_name' => $creator->name,
            'subject' => $subject,
            'meta' => [
                'approved_area_names' => $approvedAreaNames->all(),
                'remaining_area_names' => $remainingAreaNames->all(),
                'approved_by' => $approver->name,
                'dashboard_url' => $dashboardUrl,
            ],
            'sent_at' => now(),
        ]);
    }

    private function formatDateRange(Booking $booking): string
    {
        return sprintf(
            '%s to %s',
            $booking->start_date->format('M j, Y'),
            $booking->end_date->format('M j, Y'),
        );
    }

    private function paymentMethodLabel(string $paymentMethod): string
    {
        return config("thunderpoint.payment_methods.{$paymentMethod}")
            ?? strtoupper(str_replace('_', ' ', $paymentMethod));
    }
}