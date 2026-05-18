<?php

namespace Tests\Feature;

use App\Models\BookingActivityLog;
use App\Models\Booking;
use App\Models\LivingArea;
use App\Models\NotificationLog;
use App\Models\User;
use Database\Seeders\LivingAreaSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PoobahAdminAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(LivingAreaSeeder::class);
    }

    public function test_poobah_can_access_admin_screen_for_managed_area(): void
    {
        $poobah = User::factory()->create();
        $managedArea = LivingArea::query()->firstOrFail();
        $poobah->managedAreas()->attach($managedArea->id, ['role' => 'poobah']);

        $response = $this->actingAs($poobah)->get(route('admin.index'));

        $response->assertOk();
        $response->assertSee($managedArea->name);
    }

    public function test_poobah_can_approve_only_their_managed_area_rows(): void
    {
        $poobah = User::factory()->create();
        $guest = User::factory()->create();
        $areas = LivingArea::query()->take(2)->get();
        $poobah->managedAreas()->attach($areas->first()->id, ['role' => 'poobah']);

        foreach ($areas as $area) {
            Booking::query()->create([
                'booking_group' => 'poobah-group',
                'living_area_id' => $area->id,
                'created_by' => $guest->id,
                'guest_name' => 'Poobah Approval Guest',
                'start_date' => '2026-11-01',
                'end_date' => '2026-11-03',
                'status' => Booking::STATUS_DRAFT,
                'amount_cents' => 6000,
                'payment_status' => Booking::PAYMENT_PENDING,
                'payment_method' => 'paypal',
            ]);
        }

        $this->actingAs($poobah)
            ->patch(route('admin.bookings.approve', 'poobah-group'))
            ->assertRedirect(route('admin.index', absolute: false));

        $this->assertDatabaseHas('bookings', [
            'booking_group' => 'poobah-group',
            'living_area_id' => $areas->first()->id,
            'status' => Booking::STATUS_ACTIVE,
            'approved_by' => $poobah->id,
        ]);

        $this->assertDatabaseHas('bookings', [
            'booking_group' => 'poobah-group',
            'living_area_id' => $areas->last()->id,
            'status' => Booking::STATUS_DRAFT,
            'approved_by' => null,
        ]);
    }

    public function test_poobah_can_update_managed_area_settings_but_not_other_areas(): void
    {
        $poobah = User::factory()->create();
        $managedArea = LivingArea::query()->firstOrFail();
        $otherArea = LivingArea::query()->skip(1)->firstOrFail();
        $poobah->managedAreas()->attach($managedArea->id, ['role' => 'poobah']);

        $this->actingAs($poobah)
            ->patch(route('admin.living-areas.update', $managedArea), [
                'name' => 'Updated Area Name',
                'booking_message' => 'Bring your own sheets.',
            ])
            ->assertRedirect(route('admin.index', absolute: false));

        $this->assertDatabaseHas('living_areas', [
            'id' => $managedArea->id,
            'name' => 'Updated Area Name',
            'booking_message' => 'Bring your own sheets.',
        ]);

        $this->actingAs($poobah)
            ->patch(route('admin.living-areas.update', $otherArea), [
                'name' => 'Should Fail',
                'booking_message' => 'No access.',
            ])
            ->assertForbidden();
    }

    public function test_admin_can_assign_poobah_role_by_living_area(): void
    {
        $admin = User::factory()->create(['site_role' => 'admin']);
        $user = User::factory()->create();
        $area = LivingArea::query()->firstOrFail();

        $this->actingAs($admin)
            ->patch(route('admin.living-areas.managers.update', [$area, $user]), [
                'role' => 'poobah',
            ])
            ->assertRedirect(route('admin.index', absolute: false));

        $this->assertTrue($user->fresh()->managesArea($area->id));

        $this->actingAs($admin)
            ->patch(route('admin.living-areas.managers.update', [$area, $user]), [
                'role' => 'standard',
            ])
            ->assertRedirect(route('admin.index', absolute: false));

        $this->assertFalse($user->fresh()->managesArea($area->id));
    }

    public function test_poobah_can_create_a_confirmed_stay_for_a_managed_area(): void
    {
        $poobah = User::factory()->create();
        $managedArea = LivingArea::query()->firstOrFail();
        $poobah->managedAreas()->attach($managedArea->id, ['role' => 'poobah']);

        $response = $this->actingAs($poobah)->post(route('admin.bookings.active.store'), [
            'living_area_ids' => [$managedArea->id],
            'guest_name' => 'Managed Active Guest',
            'start_date' => '2026-12-10',
            'end_date' => '2026-12-12',
            'note' => 'Directly confirmed by poobah.',
            'payment_method' => 'pay_later',
        ]);

        $response->assertRedirect(route('admin.index', absolute: false));

        $booking = Booking::query()->firstOrFail();

        $this->assertDatabaseHas('bookings', [
            'id' => $booking->id,
            'living_area_id' => $managedArea->id,
            'guest_name' => 'Managed Active Guest',
            'status' => Booking::STATUS_ACTIVE,
            'approved_by' => $poobah->id,
        ]);

        $this->assertDatabaseHas('booking_activity_logs', [
            'booking_id' => $booking->id,
            'action' => 'active_booking_created',
            'actor_id' => $poobah->id,
        ]);
    }

    public function test_poobah_cannot_create_a_confirmed_stay_for_an_unmanaged_area(): void
    {
        $poobah = User::factory()->create();
        $managedArea = LivingArea::query()->firstOrFail();
        $otherArea = LivingArea::query()->skip(1)->firstOrFail();
        $poobah->managedAreas()->attach($managedArea->id, ['role' => 'poobah']);

        $this->actingAs($poobah)
            ->post(route('admin.bookings.active.store'), [
                'living_area_ids' => [$otherArea->id],
                'guest_name' => 'Forbidden Active Guest',
                'start_date' => '2026-12-15',
                'end_date' => '2026-12-16',
                'payment_method' => 'pay_later',
            ])
            ->assertForbidden();

        $this->assertDatabaseMissing('bookings', [
            'guest_name' => 'Forbidden Active Guest',
        ]);
    }

    public function test_poobah_admin_screen_shows_only_scoped_activity_and_notification_logs(): void
    {
        $poobah = User::factory()->create();
        $guest = User::factory()->create();
        $managedArea = LivingArea::query()->firstOrFail();
        $hiddenArea = LivingArea::query()->skip(1)->firstOrFail();
        $poobah->managedAreas()->attach($managedArea->id, ['role' => 'poobah']);

        $managedBooking = Booking::query()->create([
            'booking_group' => 'managed-log-group',
            'living_area_id' => $managedArea->id,
            'created_by' => $guest->id,
            'guest_name' => 'Scoped Guest',
            'start_date' => '2026-12-20',
            'end_date' => '2026-12-21',
            'status' => Booking::STATUS_DRAFT,
            'amount_cents' => 4000,
            'payment_status' => Booking::PAYMENT_UNPAID,
            'payment_method' => 'pay_later',
        ]);

        $hiddenBooking = Booking::query()->create([
            'booking_group' => 'hidden-log-group',
            'living_area_id' => $hiddenArea->id,
            'created_by' => $guest->id,
            'guest_name' => 'Hidden Guest',
            'start_date' => '2026-12-22',
            'end_date' => '2026-12-23',
            'status' => Booking::STATUS_DRAFT,
            'amount_cents' => 4000,
            'payment_status' => Booking::PAYMENT_UNPAID,
            'payment_method' => 'pay_later',
        ]);

        BookingActivityLog::query()->create([
            'booking_id' => $managedBooking->id,
            'booking_group' => $managedBooking->booking_group,
            'actor_id' => $poobah->id,
            'action' => 'active_booking_created',
            'to_status' => Booking::STATUS_ACTIVE,
        ]);

        BookingActivityLog::query()->create([
            'booking_id' => $hiddenBooking->id,
            'booking_group' => $hiddenBooking->booking_group,
            'actor_id' => $poobah->id,
            'action' => 'active_booking_created',
            'to_status' => Booking::STATUS_ACTIVE,
        ]);

        NotificationLog::query()->create([
            'booking_id' => $managedBooking->id,
            'booking_group' => $managedBooking->booking_group,
            'user_id' => $guest->id,
            'notification_type' => 'draft_submitted',
            'recipient_email' => 'managed@example.com',
            'recipient_name' => 'Managed Recipient',
            'subject' => 'Managed Notification',
            'sent_at' => now(),
        ]);

        NotificationLog::query()->create([
            'booking_id' => $hiddenBooking->id,
            'booking_group' => $hiddenBooking->booking_group,
            'user_id' => $guest->id,
            'notification_type' => 'draft_submitted',
            'recipient_email' => 'hidden@example.com',
            'recipient_name' => 'Hidden Recipient',
            'subject' => 'Hidden Notification',
            'sent_at' => now(),
        ]);

        $response = $this->actingAs($poobah)->get(route('admin.index'));

        $response->assertOk();
        $response->assertSee('Scoped Guest');
        $response->assertSee('Managed Notification');
        $response->assertDontSee('Hidden Guest');
        $response->assertDontSee('Hidden Notification');
    }

    public function test_poobah_can_update_a_confirmed_stay_for_managed_areas(): void
    {
        $poobah = User::factory()->create();
        $guest = User::factory()->create();
        $managedArea = LivingArea::query()->firstOrFail();
        $poobah->managedAreas()->attach($managedArea->id, ['role' => 'poobah']);

        Booking::query()->create([
            'booking_group' => 'poobah-edit-group',
            'living_area_id' => $managedArea->id,
            'created_by' => $guest->id,
            'approved_by' => $poobah->id,
            'guest_name' => 'Old Managed Guest',
            'start_date' => '2027-02-01',
            'end_date' => '2027-02-02',
            'status' => Booking::STATUS_ACTIVE,
            'amount_cents' => 2000,
            'payment_status' => Booking::PAYMENT_UNPAID,
            'payment_method' => 'pay_later',
            'approved_at' => now(),
        ]);

        $this->actingAs($poobah)
            ->patch(route('admin.bookings.update', 'poobah-edit-group'), [
                'living_area_ids' => [$managedArea->id],
                'guest_name' => 'New Managed Guest',
                'start_date' => '2027-02-03',
                'end_date' => '2027-02-04',
                'payment_method' => 'paypal',
            ])
            ->assertRedirect(route('admin.index', absolute: false));

        $this->assertDatabaseHas('bookings', [
            'guest_name' => 'New Managed Guest',
            'living_area_id' => $managedArea->id,
            'status' => Booking::STATUS_ACTIVE,
        ]);
        $this->assertDatabaseHas('bookings', [
            'booking_group' => 'poobah-edit-group',
            'status' => Booking::STATUS_CANCELLED,
        ]);
    }

    public function test_poobah_can_cancel_a_confirmed_stay_for_managed_areas(): void
    {
        $poobah = User::factory()->create();
        $guest = User::factory()->create();
        $managedArea = LivingArea::query()->firstOrFail();
        $poobah->managedAreas()->attach($managedArea->id, ['role' => 'poobah']);

        Booking::query()->create([
            'booking_group' => 'poobah-cancel-group',
            'living_area_id' => $managedArea->id,
            'created_by' => $guest->id,
            'approved_by' => $poobah->id,
            'guest_name' => 'Managed Cancel Guest',
            'start_date' => '2027-02-05',
            'end_date' => '2027-02-06',
            'status' => Booking::STATUS_ACTIVE,
            'amount_cents' => 2000,
            'payment_status' => Booking::PAYMENT_UNPAID,
            'payment_method' => 'pay_later',
            'approved_at' => now(),
        ]);

        $this->actingAs($poobah)
            ->patch(route('admin.bookings.cancel', 'poobah-cancel-group'))
            ->assertRedirect(route('admin.index', absolute: false));

        $this->assertDatabaseHas('bookings', [
            'booking_group' => 'poobah-cancel-group',
            'status' => Booking::STATUS_CANCELLED,
            'cancelled_by' => $poobah->id,
        ]);
    }
}