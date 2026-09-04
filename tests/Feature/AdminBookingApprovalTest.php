<?php

namespace Tests\Feature;

use App\Mail\BookingApprovedMail;
use App\Mail\DraftBookingSubmittedMail;
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

    public function test_admin_can_approve_one_draft_even_when_another_draft_overlaps(): void
    {
        $admin = User::factory()->create(['site_role' => 'admin']);
        $guest = User::factory()->create();
        $area = LivingArea::query()->firstOrFail();

        Booking::query()->create([
            'booking_group' => 'existing-draft-group',
            'living_area_id' => $area->id,
            'created_by' => $guest->id,
            'guest_name' => 'Existing Draft Guest',
            'start_date' => '2026-10-02',
            'end_date' => '2026-10-05',
            'status' => Booking::STATUS_DRAFT,
            'amount_cents' => 8000,
            'payment_status' => Booking::PAYMENT_PENDING,
            'payment_method' => 'paypal',
        ]);

        Booking::query()->create([
            'booking_group' => 'approval-draft-group',
            'living_area_id' => $area->id,
            'created_by' => $guest->id,
            'guest_name' => 'Approval Draft Guest',
            'start_date' => '2026-10-03',
            'end_date' => '2026-10-04',
            'status' => Booking::STATUS_DRAFT,
            'amount_cents' => 4000,
            'payment_status' => Booking::PAYMENT_PENDING,
            'payment_method' => 'paypal',
        ]);

        $this->actingAs($admin)
            ->patch(route('admin.bookings.approve', 'approval-draft-group'))
            ->assertRedirect(route('admin.index', absolute: false));

        $this->assertDatabaseHas('bookings', [
            'booking_group' => 'approval-draft-group',
            'status' => Booking::STATUS_ACTIVE,
            'approved_by' => $admin->id,
        ]);
        $this->assertDatabaseHas('bookings', [
            'booking_group' => 'existing-draft-group',
            'status' => Booking::STATUS_DRAFT,
        ]);
    }

    public function test_admin_cannot_approve_a_draft_when_an_active_booking_overlaps(): void
    {
        $admin = User::factory()->create(['site_role' => 'admin']);
        $guest = User::factory()->create();
        $area = LivingArea::query()->firstOrFail();

        Booking::query()->create([
            'booking_group' => 'active-conflict-group',
            'living_area_id' => $area->id,
            'created_by' => $guest->id,
            'approved_by' => $admin->id,
            'guest_name' => 'Existing Active Guest',
            'start_date' => '2026-10-03',
            'end_date' => '2026-10-06',
            'status' => Booking::STATUS_ACTIVE,
            'amount_cents' => 8000,
            'payment_status' => Booking::PAYMENT_SUBMITTED,
            'payment_method' => 'paypal',
            'approved_at' => now(),
        ]);

        Booking::query()->create([
            'booking_group' => 'approval-conflict-group',
            'living_area_id' => $area->id,
            'created_by' => $guest->id,
            'guest_name' => 'Conflicting Draft Guest',
            'start_date' => '2026-10-04',
            'end_date' => '2026-10-05',
            'status' => Booking::STATUS_DRAFT,
            'amount_cents' => 4000,
            'payment_status' => Booking::PAYMENT_PENDING,
            'payment_method' => 'paypal',
        ]);

        $this->followingRedirects()
            ->actingAs($admin)
            ->from(route('admin.index'))
            ->patch(route('admin.bookings.approve', 'approval-conflict-group'))
            ->assertOk()
            ->assertSee('data-flash-toast-region', false)
            ->assertSee('These living areas already have a blocking booking in that range: '.$area->name.'.')
            ->assertDontSee('There was a problem saving one of the admin forms.');

        $this->assertDatabaseHas('bookings', [
            'booking_group' => 'approval-conflict-group',
            'status' => Booking::STATUS_DRAFT,
            'approved_by' => null,
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

    public function test_admin_can_create_a_confirmed_booking_group_directly(): void
    {
        $admin = User::factory()->create(['site_role' => 'admin']);
        $areas = LivingArea::query()->take(2)->get();

        Booking::query()->create([
            'booking_group' => 'overlapping-draft-group',
            'living_area_id' => $areas->first()->id,
            'created_by' => $admin->id,
            'guest_name' => 'Existing Draft Guest',
            'start_date' => '2026-12-27',
            'end_date' => '2026-12-29',
            'status' => Booking::STATUS_DRAFT,
            'amount_cents' => 6000,
            'payment_status' => Booking::PAYMENT_PENDING,
            'payment_method' => 'paypal',
        ]);

        $response = $this->actingAs($admin)->post(route('bookings.store'), [
            'living_area_ids' => $areas->pluck('id')->all(),
            'guest_name' => 'Admin Direct Guest',
            'start_date' => '2026-12-27',
            'end_date' => '2026-12-28',
            'payment_method' => 'pay_later',
        ]);

        $response->assertRedirect(route('dashboard', absolute: false));

        $this->assertSame(2, Booking::query()->where('guest_name', 'Admin Direct Guest')->count());
        $this->assertSame(1, Booking::query()->where('guest_name', 'Admin Direct Guest')->distinct('booking_group')->count('booking_group'));
        $this->assertDatabaseHas('bookings', [
            'guest_name' => 'Admin Direct Guest',
            'status' => Booking::STATUS_ACTIVE,
            'approved_by' => $admin->id,
        ]);
        $this->assertSame(2, BookingActivityLog::query()->where('action', 'active_booking_created')->where('actor_id', $admin->id)->count());
    }

    public function test_admin_can_create_a_draft_booking_from_the_dashboard_form(): void
    {
        Mail::fake();

        $admin = User::factory()->create([
            'site_role' => 'admin',
            'email' => 'admin@thunderpoint.test',
        ]);
        $areas = LivingArea::query()->take(2)->get();

        $response = $this->actingAs($admin)->post(route('bookings.store'), [
            'living_area_ids' => $areas->pluck('id')->all(),
            'guest_name' => 'Admin Draft Guest',
            'start_date' => '2026-12-29',
            'end_date' => '2026-12-30',
            'payment_method' => 'pay_later',
            'book_as_draft' => '1',
        ]);

        $response->assertRedirect(route('dashboard', absolute: false));

        $this->assertDatabaseHas('bookings', [
            'guest_name' => 'Admin Draft Guest',
            'status' => Booking::STATUS_DRAFT,
            'approved_by' => null,
        ]);
        $this->assertSame(2, BookingActivityLog::query()->where('action', 'draft_submitted')->where('actor_id', $admin->id)->count());
        Mail::assertSent(DraftBookingSubmittedMail::class, fn (DraftBookingSubmittedMail $mail) => $mail->hasTo($admin->email));
        $this->assertSame(1, NotificationLog::query()->where('notification_type', 'draft_submitted')->count());
    }

    public function test_admin_can_update_a_confirmed_booking_group(): void
    {
        $admin = User::factory()->create(['site_role' => 'admin']);
        $guest = User::factory()->create();
        $areas = LivingArea::query()->take(2)->get();

        foreach ($areas as $area) {
            Booking::query()->create([
                'booking_group' => 'editable-group',
                'living_area_id' => $area->id,
                'created_by' => $guest->id,
                'approved_by' => $admin->id,
                'guest_name' => 'Original Active Guest',
                'start_date' => '2027-01-05',
                'end_date' => '2027-01-06',
                'status' => Booking::STATUS_ACTIVE,
                'amount_cents' => 4000,
                'payment_status' => Booking::PAYMENT_UNPAID,
                'payment_method' => 'pay_later',
                'approved_at' => now(),
            ]);
        }

        $originalBookingIds = Booking::query()
            ->where('booking_group', 'editable-group')
            ->orderBy('id')
            ->pluck('id')
            ->all();

        $this->actingAs($admin)
            ->get(route('admin.index'))
            ->assertOk()
            ->assertSee('data-date-range-picker', false)
            ->assertSee('Arrival date')
            ->assertSee('Departure date')
            ->assertDontSee('type="date"', false);

        $response = $this->actingAs($admin)->patch(route('admin.bookings.update', 'editable-group'), [
            'living_area_ids' => $areas->pluck('id')->all(),
            'guest_name' => 'Updated Active Guest',
            'start_date' => '2027-01-08',
            'end_date' => '2027-01-10',
            'note' => 'Updated stay details.',
            'payment_method' => 'venmo',
            'payment_reference' => 'venmo-edit-1',
        ]);

        $response->assertRedirect(route('admin.index', absolute: false));

        $this->assertSame(2, Booking::query()->where('booking_group', 'editable-group')->where('guest_name', 'Updated Active Guest')->where('status', Booking::STATUS_ACTIVE)->count());
        $this->assertSame(
            $originalBookingIds,
            Booking::query()->where('booking_group', 'editable-group')->orderBy('id')->pluck('id')->all(),
        );
        $this->assertSame(0, Booking::query()->where('booking_group', 'editable-group')->where('status', Booking::STATUS_CANCELLED)->count());
        $this->assertSame(2, BookingActivityLog::query()->where('action', 'booking_updated')->count());
        $this->assertSame(0, BookingActivityLog::query()->where('action', 'booking_cancelled')->count());
    }

    public function test_admin_can_cancel_a_confirmed_booking_group(): void
    {
        $admin = User::factory()->create(['site_role' => 'admin']);
        $guest = User::factory()->create();
        $areas = LivingArea::query()->take(2)->get();

        foreach ($areas as $area) {
            Booking::query()->create([
                'booking_group' => 'cancel-group',
                'living_area_id' => $area->id,
                'created_by' => $guest->id,
                'approved_by' => $admin->id,
                'guest_name' => 'Cancellable Guest',
                'start_date' => '2027-01-12',
                'end_date' => '2027-01-13',
                'status' => Booking::STATUS_ACTIVE,
                'amount_cents' => 4000,
                'payment_status' => Booking::PAYMENT_UNPAID,
                'payment_method' => 'pay_later',
                'approved_at' => now(),
            ]);
        }

        $response = $this->actingAs($admin)->patch(route('admin.bookings.cancel', 'cancel-group'));

        $response->assertRedirect(route('admin.index', absolute: false));

        $this->assertSame(2, Booking::query()->where('booking_group', 'cancel-group')->where('status', Booking::STATUS_CANCELLED)->count());
        $this->assertSame(2, BookingActivityLog::query()->where('booking_group', 'cancel-group')->where('action', 'booking_cancelled')->count());
    }

    public function test_admin_screen_shows_inline_booking_history_details(): void
    {
        $admin = User::factory()->create(['site_role' => 'admin']);
        $guest = User::factory()->create();
        $area = LivingArea::query()->firstOrFail();

        $booking = Booking::query()->create([
            'booking_group' => 'history-group',
            'living_area_id' => $area->id,
            'created_by' => $guest->id,
            'guest_name' => 'History Guest',
            'start_date' => '2027-01-20',
            'end_date' => '2027-01-21',
            'status' => Booking::STATUS_ACTIVE,
            'amount_cents' => 4000,
            'payment_status' => Booking::PAYMENT_UNPAID,
            'payment_method' => 'pay_later',
        ]);

        BookingActivityLog::query()->create([
            'booking_id' => $booking->id,
            'booking_group' => 'history-group',
            'actor_id' => $admin->id,
            'action' => 'booking_updated',
            'to_status' => Booking::STATUS_ACTIVE,
        ]);

        NotificationLog::query()->create([
            'booking_id' => $booking->id,
            'booking_group' => 'history-group',
            'user_id' => $guest->id,
            'notification_type' => 'booking_approved',
            'recipient_email' => $guest->email,
            'recipient_name' => $guest->name,
            'subject' => 'History Email',
            'sent_at' => now(),
        ]);

        $this->actingAs($admin)
            ->get(route('admin.index'))
            ->assertOk()
            ->assertSee('Booking history')
            ->assertSee('History Email')
            ->assertSee('Confirmed stay updated for');
    }
}
