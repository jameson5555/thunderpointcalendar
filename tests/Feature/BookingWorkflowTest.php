<?php

namespace Tests\Feature;

use App\Mail\DraftBookingSubmittedMail;
use App\Models\Booking;
use App\Models\BookingActivityLog;
use App\Models\LivingArea;
use App\Models\NotificationLog;
use App\Models\User;
use Carbon\CarbonImmutable;
use Database\Seeders\LivingAreaSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class BookingWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(LivingAreaSeeder::class);
    }

    public function test_dashboard_shows_separate_arrival_and_departure_fields(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('data-date-range-picker', false)
            ->assertSee('data-calendar-booking-form', false)
            ->assertSee('Arrival date')
            ->assertSee('Departure date')
            ->assertSee('MM/DD/YYYY')
            ->assertDontSee('type="date"', false);
    }

    public function test_dashboard_uses_calendar_modal_before_your_bookings(): void
    {
        CarbonImmutable::setTestNow('2026-09-02 12:00:00');

        try {
            $user = User::factory()->create();
            $area = LivingArea::query()->firstOrFail();

            Booking::query()->create([
                'booking_group' => 'mobile-calendar-group',
                'living_area_id' => $area->id,
                'created_by' => $user->id,
                'guest_name' => 'Mobile Calendar Guest',
                'start_date' => '2026-09-10',
                'end_date' => '2026-09-12',
                'status' => Booking::STATUS_ACTIVE,
                'amount_cents' => 6000,
                'payment_status' => Booking::PAYMENT_SUBMITTED,
                'payment_method' => 'paypal',
            ]);

            $this->actingAs($user)
                ->get(route('dashboard'))
                ->assertOk()
                ->assertSeeInOrder([
                    'data-calendar-day',
                    'data-calendar-booking-trigger',
                    'data-calendar-modal',
                    'data-calendar-day-agenda',
                    'data-calendar-booking-form',
                    'data-your-bookings',
                ], false)
                ->assertDontSee('data-booking-jump', false)
                ->assertDontSee('data-booking-form', false)
                ->assertSee('Mobile Calendar Guest')
                ->assertSee($area->name)
                ->assertSee('Sep 10, 2026')
                ->assertSee('Sep 12, 2026');
        } finally {
            CarbonImmutable::setTestNow();
        }
    }

    public function test_your_bookings_excludes_stays_that_ended_before_today(): void
    {
        CarbonImmutable::setTestNow('2026-09-03 12:00:00');

        try {
            $user = User::factory()->create();
            $area = LivingArea::query()->firstOrFail();
            $defaults = [
                'living_area_id' => $area->id,
                'created_by' => $user->id,
                'status' => Booking::STATUS_ACTIVE,
                'amount_cents' => 6000,
                'payment_status' => Booking::PAYMENT_SUBMITTED,
                'payment_method' => 'paypal',
            ];

            Booking::query()->create($defaults + [
                'booking_group' => 'past-stay',
                'guest_name' => 'Past Stay',
                'start_date' => '2026-08-30',
                'end_date' => '2026-09-02',
            ]);
            Booking::query()->create($defaults + [
                'booking_group' => 'ending-today',
                'guest_name' => 'Ending Today',
                'start_date' => '2026-09-01',
                'end_date' => '2026-09-03',
            ]);
            Booking::query()->create($defaults + [
                'booking_group' => 'future-stay',
                'guest_name' => 'Future Stay',
                'start_date' => '2026-09-10',
                'end_date' => '2026-09-12',
            ]);

            $response = $this->actingAs($user)->get(route('dashboard'));
            $bookingGroups = $response->viewData('myBookings')->pluck('group');

            $response->assertOk();
            $this->assertNotContains('past-stay', $bookingGroups);
            $this->assertContains('ending-today', $bookingGroups);
            $this->assertContains('future-stay', $bookingGroups);
        } finally {
            CarbonImmutable::setTestNow();
        }
    }

    public function test_approved_users_can_create_a_draft_booking(): void
    {
        $user = User::factory()->create();
        $area = LivingArea::query()->firstOrFail();

        $response = $this->actingAs($user)->post(route('bookings.store'), [
            'living_area_ids' => [$area->id],
            'guest_name' => 'Jamie Camper',
            'start_date' => '2026-06-01',
            'end_date' => '2026-06-03',
            'note' => 'Bringing extra towels.',
            'payment_method' => 'pay_later',
        ]);

        $response->assertRedirect(route('dashboard', absolute: false));

        $this->assertDatabaseHas('bookings', [
            'living_area_id' => $area->id,
            'guest_name' => 'Jamie Camper',
            'status' => Booking::STATUS_DRAFT,
            'amount_cents' => 6000,
            'payment_status' => Booking::PAYMENT_UNPAID,
        ]);
    }

    public function test_draft_bookings_can_overlap_other_draft_bookings(): void
    {
        $firstUser = User::factory()->create();
        $secondUser = User::factory()->create();
        $area = LivingArea::query()->firstOrFail();

        $this->actingAs($firstUser)->post(route('bookings.store'), [
            'living_area_ids' => [$area->id],
            'guest_name' => 'First Draft Guest',
            'start_date' => '2026-06-10',
            'end_date' => '2026-06-12',
            'payment_method' => 'pay_later',
        ])->assertRedirect(route('dashboard', absolute: false));

        $this->actingAs($secondUser)->post(route('bookings.store'), [
            'living_area_ids' => [$area->id],
            'guest_name' => 'Second Draft Guest',
            'start_date' => '2026-06-11',
            'end_date' => '2026-06-13',
            'payment_method' => 'pay_later',
        ])->assertRedirect(route('dashboard', absolute: false));

        $this->assertSame(2, Booking::query()->where('living_area_id', $area->id)->where('status', Booking::STATUS_DRAFT)->count());
    }

    public function test_overlapping_blocking_bookings_are_rejected(): void
    {
        $user = User::factory()->create();
        $area = LivingArea::query()->firstOrFail();

        Booking::query()->create([
            'booking_group' => 'existing-group',
            'living_area_id' => $area->id,
            'created_by' => $user->id,
            'guest_name' => 'Existing Guest',
            'start_date' => '2026-07-10',
            'end_date' => '2026-07-12',
            'status' => Booking::STATUS_ACTIVE,
            'amount_cents' => 6000,
            'payment_status' => Booking::PAYMENT_SUBMITTED,
            'payment_method' => 'paypal',
            'payment_reference' => 'abc123',
        ]);

        $response = $this->followingRedirects()->from(route('dashboard'))->actingAs($user)->post(route('bookings.store'), [
            'living_area_ids' => [$area->id],
            'guest_name' => 'New Guest',
            'start_date' => '2026-07-12',
            'end_date' => '2026-07-14',
            'payment_method' => 'pay_later',
        ]);

        $response
            ->assertOk()
            ->assertSee('data-flash-toast-region', false)
            ->assertSee('These living areas already have a blocking booking in that range: '.$area->name.'.')
            ->assertDontSee('Your booking could not be saved.');

        $this->assertDatabaseCount('bookings', 1);
    }

    public function test_poobah_rate_is_used_for_managed_areas(): void
    {
        $user = User::factory()->create();
        $area = LivingArea::query()->firstOrFail();
        $user->managedAreas()->attach($area->id, ['role' => 'poobah']);

        $this->actingAs($user)->post(route('bookings.store'), [
            'living_area_ids' => [$area->id],
            'guest_name' => 'Poobah Guest',
            'start_date' => '2026-08-01',
            'end_date' => '2026-08-02',
            'payment_method' => 'pay_later',
        ]);

        $this->assertDatabaseHas('bookings', [
            'living_area_id' => $area->id,
            'amount_cents' => 2000,
        ]);
    }

    public function test_full_property_weekly_rate_is_used_when_all_areas_are_selected(): void
    {
        $user = User::factory()->create();
        $areaIds = LivingArea::query()->pluck('id')->all();

        $this->actingAs($user)->post(route('bookings.store'), [
            'living_area_ids' => $areaIds,
            'guest_name' => 'Whole Camp Guest',
            'start_date' => '2026-08-10',
            'end_date' => '2026-08-16',
            'payment_method' => 'pay_later',
        ]);

        $this->assertDatabaseCount('bookings', 4);
        $this->assertSame(1, Booking::query()->distinct('booking_group')->count('booking_group'));
        $this->assertEquals(50000, Booking::query()->firstOrFail()->amount_cents);
    }

    public function test_users_can_update_payment_details_for_their_draft_booking(): void
    {
        $user = User::factory()->create();
        $area = LivingArea::query()->firstOrFail();

        $booking = Booking::query()->create([
            'booking_group' => 'payment-group',
            'living_area_id' => $area->id,
            'created_by' => $user->id,
            'guest_name' => 'Draft Guest',
            'start_date' => '2026-09-01',
            'end_date' => '2026-09-03',
            'status' => Booking::STATUS_DRAFT,
            'amount_cents' => 6000,
            'payment_status' => Booking::PAYMENT_UNPAID,
            'payment_method' => 'pay_later',
        ]);

        $response = $this->actingAs($user)->patch(route('bookings.payment.update', $booking->booking_group), [
            'payment_method' => 'venmo',
            'payment_reference' => 'venmo-123',
        ]);

        $response->assertRedirect(route('dashboard', absolute: false));

        $this->assertDatabaseHas('bookings', [
            'booking_group' => 'payment-group',
            'payment_method' => 'venmo',
            'payment_reference' => 'venmo-123',
            'payment_status' => Booking::PAYMENT_SUBMITTED,
        ]);
    }

    public function test_draft_booking_submission_sends_emails_and_logs_activity(): void
    {
        Mail::fake();

        $guest = User::factory()->create(['name' => 'Jamie Booker']);
        $admin = User::factory()->create([
            'site_role' => 'admin',
            'email' => 'admin@thunderpoint.test',
        ]);
        $poobah = User::factory()->create(['email' => 'poobah@thunderpoint.test']);
        $area = LivingArea::query()->firstOrFail();

        $poobah->managedAreas()->attach($area->id, ['role' => 'poobah']);

        $response = $this->actingAs($guest)->post(route('bookings.store'), [
            'living_area_ids' => [$area->id],
            'guest_name' => 'Email Guest',
            'start_date' => '2026-11-01',
            'end_date' => '2026-11-03',
            'payment_method' => 'venmo',
            'payment_reference' => 'venmo-555',
        ]);

        $response->assertRedirect(route('dashboard', absolute: false));

        $booking = Booking::query()->firstOrFail();

        Mail::assertSent(DraftBookingSubmittedMail::class, 2);
        Mail::assertSent(DraftBookingSubmittedMail::class, fn (DraftBookingSubmittedMail $mail) => $mail->hasTo($admin->email));
        Mail::assertSent(DraftBookingSubmittedMail::class, fn (DraftBookingSubmittedMail $mail) => $mail->hasTo($poobah->email));

        $this->assertDatabaseHas('booking_activity_logs', [
            'booking_id' => $booking->id,
            'booking_group' => $booking->booking_group,
            'actor_id' => $guest->id,
            'action' => 'draft_submitted',
            'to_status' => Booking::STATUS_DRAFT,
        ]);

        $this->assertSame(2, NotificationLog::query()->where('booking_group', $booking->booking_group)->count());
        $this->assertDatabaseHas('notification_logs', [
            'booking_group' => $booking->booking_group,
            'notification_type' => 'draft_submitted',
            'recipient_email' => $admin->email,
        ]);
        $this->assertDatabaseHas('notification_logs', [
            'booking_group' => $booking->booking_group,
            'notification_type' => 'draft_submitted',
            'recipient_email' => $poobah->email,
        ]);
        $this->assertSame(1, BookingActivityLog::query()->where('booking_group', $booking->booking_group)->where('action', 'draft_submitted')->count());
    }
}
