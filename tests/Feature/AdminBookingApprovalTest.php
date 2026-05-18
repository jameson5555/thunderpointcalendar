<?php

namespace Tests\Feature;

use App\Mail\BookingApprovedMail;
use App\Models\BookingActivityLog;
use App\Models\Booking;
use App\Models\LivingArea;
use App\Models\NotificationLog;
use App\Models\User;
use Database\Seeders\LivingAreaSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class AdminBookingApprovalTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(LivingAreaSeeder::class);
    }

    public function test_admin_can_approve_a_draft_booking_group(): void
    {
        $admin = User::factory()->create(['site_role' => 'admin']);
        $guest = User::factory()->create();
        $areas = LivingArea::query()->take(2)->get();

        foreach ($areas as $area) {
            Booking::query()->create([
                'booking_group' => 'approval-group',
                'living_area_id' => $area->id,
                'created_by' => $guest->id,
                'guest_name' => 'Approval Guest',
                'start_date' => '2026-10-01',
                'end_date' => '2026-10-04',
                'status' => Booking::STATUS_DRAFT,
                'amount_cents' => 8000,
                'payment_status' => Booking::PAYMENT_PENDING,
                'payment_method' => 'paypal',
            ]);
        }

        $response = $this->actingAs($admin)->patch(route('admin.bookings.approve', 'approval-group'));

        $response->assertRedirect(route('admin.index', absolute: false));

        $this->assertDatabaseHas('bookings', [
            'booking_group' => 'approval-group',
            'status' => Booking::STATUS_ACTIVE,
            'approved_by' => $admin->id,
        ]);
    }

    public function test_non_admin_users_cannot_approve_bookings(): void
    {
        $user = User::factory()->create();
        $area = LivingArea::query()->firstOrFail();

        Booking::query()->create([
            'booking_group' => 'forbidden-group',
            'living_area_id' => $area->id,
            'created_by' => $user->id,
            'guest_name' => 'Forbidden Guest',
            'start_date' => '2026-10-10',
            'end_date' => '2026-10-11',
            'status' => Booking::STATUS_DRAFT,
            'amount_cents' => 4000,
            'payment_status' => Booking::PAYMENT_UNPAID,
            'payment_method' => 'pay_later',
        ]);

        $this->actingAs($user)
            ->patch(route('admin.bookings.approve', 'forbidden-group'))
            ->assertForbidden();
    }

    public function test_approving_a_booking_group_notifies_the_creator_and_logs_state_changes(): void
    {
        Mail::fake();

        $admin = User::factory()->create([
            'site_role' => 'admin',
            'email' => 'admin@thunderpoint.test',
        ]);
        $guest = User::factory()->create(['email' => 'guest@thunderpoint.test']);
        $areas = LivingArea::query()->take(2)->get();

        foreach ($areas as $area) {
            Booking::query()->create([
                'booking_group' => 'notify-approval-group',
                'living_area_id' => $area->id,
                'created_by' => $guest->id,
                'guest_name' => 'Approval Notify Guest',
                'start_date' => '2026-12-01',
                'end_date' => '2026-12-03',
                'status' => Booking::STATUS_DRAFT,
                'amount_cents' => 6000,
                'payment_status' => Booking::PAYMENT_PENDING,
                'payment_method' => 'paypal',
            ]);
        }

        $response = $this->actingAs($admin)->patch(route('admin.bookings.approve', 'notify-approval-group'));

        $response->assertRedirect(route('admin.index', absolute: false));

        Mail::assertSent(BookingApprovedMail::class, fn (BookingApprovedMail $mail) => $mail->hasTo($guest->email));

        $this->assertSame(2, BookingActivityLog::query()->where('booking_group', 'notify-approval-group')->where('action', 'booking_approved')->count());
        $this->assertDatabaseHas('notification_logs', [
            'booking_group' => 'notify-approval-group',
            'notification_type' => 'booking_approved',
            'recipient_email' => $guest->email,
        ]);
        $this->assertSame(1, NotificationLog::query()->where('booking_group', 'notify-approval-group')->where('notification_type', 'booking_approved')->count());
    }
}