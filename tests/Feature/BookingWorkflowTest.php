<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\LivingArea;
use App\Models\User;
use Database\Seeders\LivingAreaSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookingWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(LivingAreaSeeder::class);
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

        $response = $this->from(route('dashboard'))->actingAs($user)->post(route('bookings.store'), [
            'living_area_ids' => [$area->id],
            'guest_name' => 'New Guest',
            'start_date' => '2026-07-12',
            'end_date' => '2026-07-14',
            'payment_method' => 'pay_later',
        ]);

        $response
            ->assertRedirect(route('dashboard', absolute: false))
            ->assertSessionHasErrors('living_area_ids');

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
}