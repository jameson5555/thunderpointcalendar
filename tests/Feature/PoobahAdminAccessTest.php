<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\LivingArea;
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
}