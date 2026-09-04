<?php

namespace Tests\Feature;

use App\Mail\DraftBookingUpdatedMail;
use App\Models\Booking;
use App\Models\LivingArea;
use App\Models\User;
use Carbon\CarbonImmutable;
use Database\Seeders\LivingAreaSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class InteractiveCalendarTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(LivingAreaSeeder::class);
    }

    public function test_calendar_days_include_current_adjacent_and_occupied_day_actions(): void
    {
        CarbonImmutable::setTestNow('2026-09-02 12:00:00');

        try {
            $user = User::factory()->create();
            $area = LivingArea::query()->firstOrFail();
            $this->createBooking($user, $area, 'agenda-group', Booking::STATUS_ACTIVE, '2026-09-10', '2026-09-12');

            $this->actingAs($user)
                ->get(route('dashboard', ['month' => '2026-09']))
                ->assertOk()
                ->assertSee('data-calendar-date="2026-09-03"', false)
                ->assertSee('data-calendar-date="2026-08-30"', false)
                ->assertSee('View 1 bookings on September 10, 2026')
                ->assertSee('data-calendar-day-agenda', false)
                ->assertSee('data-new-booking', false)
                ->assertSeeInOrder(['data-calendar-week', 'data-new-booking'], false);
        } finally {
            CarbonImmutable::setTestNow();
        }
    }

    public function test_calendar_deduplicates_multi_area_booking_groups_in_day_payload(): void
    {
        $user = User::factory()->create();
        $areas = LivingArea::query()->take(2)->get();

        foreach ($areas as $area) {
            $this->createBooking($user, $area, 'multi-area-calendar-group', Booking::STATUS_DRAFT, '2026-09-10', '2026-09-12');
        }

        $response = $this->actingAs($user)->get(route('dashboard', ['month' => '2026-09']));

        $response->assertOk()->assertSee('View 1 bookings on September 10, 2026');
        $groups = $response->viewData('calendarBookingGroups');
        $this->assertCount(1, $groups);
        $this->assertSame('multi-area-calendar-group', $groups->first()['group']);
        $this->assertCount(2, $groups->first()['areas']);
    }

    public function test_read_only_calendar_payload_omits_sensitive_booking_fields(): void
    {
        $owner = User::factory()->create();
        $viewer = User::factory()->create();
        $area = LivingArea::query()->firstOrFail();
        $booking = $this->createBooking($owner, $area, 'private-calendar-group', Booking::STATUS_DRAFT, '2026-09-10', '2026-09-12');
        $booking->update([
            'note' => 'PRIVATE NOTE TOKEN',
            'payment_reference' => 'PRIVATE PAYMENT TOKEN',
        ]);

        $response = $this->actingAs($viewer)->get(route('dashboard', ['month' => '2026-09']));

        $response->assertOk()->assertSee('private-calendar-group');
        $payload = $response->viewData('calendarBookingGroups')->get('private-calendar-group');
        $this->assertFalse($payload['canEdit']);
        $this->assertArrayNotHasKey('edit', $payload);
        $response->assertDontSee('PRIVATE NOTE TOKEN')->assertDontSee('PRIVATE PAYMENT TOKEN');
    }

    public function test_draft_owner_receives_edit_payload(): void
    {
        $owner = User::factory()->create();
        $area = LivingArea::query()->firstOrFail();
        $booking = $this->createBooking($owner, $area, 'owned-draft-group', Booking::STATUS_DRAFT, '2026-09-10', '2026-09-12');
        $booking->update(['note' => 'Owner editable note', 'payment_reference' => 'owner-ref']);

        $response = $this->actingAs($owner)->get(route('dashboard', ['month' => '2026-09']));

        $response->assertOk()->assertSee('Owner editable note')->assertSee('owner-ref');
        $payload = $response->viewData('calendarBookingGroups')->get('owned-draft-group');
        $this->assertTrue($payload['canEdit']);
        $this->assertSame('/bookings/owned-draft-group/draft', $payload['edit']['action']);
    }

    public function test_calendar_confirmed_edit_payload_respects_admin_and_poobah_area_permissions(): void
    {
        $creator = User::factory()->create();
        $admin = User::factory()->create(['site_role' => 'admin']);
        $poobah = User::factory()->create();
        $areas = LivingArea::query()->take(2)->get();
        $poobah->managedAreas()->attach($areas[0]->id, ['role' => 'poobah']);

        $this->createBooking($creator, $areas[0], 'managed-active-group', Booking::STATUS_ACTIVE, '2026-09-10', '2026-09-11');
        foreach ($areas as $area) {
            $this->createBooking($creator, $area, 'partially-managed-active-group', Booking::STATUS_ACTIVE, '2026-09-15', '2026-09-16');
        }

        $adminGroups = $this->actingAs($admin)
            ->get(route('dashboard', ['month' => '2026-09']))
            ->viewData('calendarBookingGroups');
        $this->assertTrue($adminGroups->get('managed-active-group')['canEdit']);
        $this->assertFalse($adminGroups->get('managed-active-group')['edit']['lockAreas']);

        $poobahGroups = $this->actingAs($poobah)
            ->get(route('dashboard', ['month' => '2026-09']))
            ->viewData('calendarBookingGroups');
        $this->assertTrue($poobahGroups->get('managed-active-group')['canEdit']);
        $this->assertFalse($poobahGroups->get('managed-active-group')['edit']['lockAreas']);
        $this->assertFalse($poobahGroups->get('partially-managed-active-group')['canEdit']);
        $this->assertArrayNotHasKey('edit', $poobahGroups->get('partially-managed-active-group'));
    }

    public function test_new_calendar_booking_returns_to_its_originating_month(): void
    {
        $user = User::factory()->create();
        $area = LivingArea::query()->firstOrFail();

        $this->actingAs($user)->post(route('bookings.store'), [
            'living_area_ids' => [$area->id],
            'guest_name' => 'Future Calendar Guest',
            'start_date' => '2027-04-10',
            'end_date' => '2027-04-12',
            'payment_method' => 'pay_later',
            'return_month' => '2027-04',
            'form_context' => 'calendar-create',
        ])->assertRedirect(route('dashboard', ['month' => '2027-04'], absolute: false));
    }

    public function test_owner_can_update_and_resynchronize_a_draft_group(): void
    {
        Mail::fake();

        $owner = User::factory()->create();
        $admin = User::factory()->create(['site_role' => 'admin']);
        $areas = LivingArea::query()->take(3)->get();
        $oldPoobah = User::factory()->create();
        $newPoobah = User::factory()->create();
        $oldPoobah->managedAreas()->attach($areas[0]->id, ['role' => 'poobah']);
        $newPoobah->managedAreas()->attach($areas[2]->id, ['role' => 'poobah']);

        foreach ($areas->take(2) as $area) {
            $this->createBooking($owner, $area, 'resync-draft-group', Booking::STATUS_DRAFT, '2026-09-10', '2026-09-11');
        }

        $response = $this->actingAs($owner)->patch(route('bookings.draft.update', 'resync-draft-group'), [
            'living_area_ids' => [$areas[1]->id, $areas[2]->id],
            'guest_name' => 'Updated Draft Guest',
            'start_date' => '2026-09-14',
            'end_date' => '2026-09-16',
            'note' => 'Updated everything.',
            'payment_method' => 'venmo',
            'payment_reference' => 'venmo-update',
            'return_month' => '2026-09',
            'form_context' => 'calendar-edit',
            'editing_group' => 'resync-draft-group',
        ]);

        $response->assertRedirect(route('dashboard', ['month' => '2026-09'], absolute: false));
        $this->assertDatabaseMissing('bookings', [
            'booking_group' => 'resync-draft-group',
            'living_area_id' => $areas[0]->id,
        ]);
        foreach ([$areas[1], $areas[2]] as $area) {
            $this->assertDatabaseHas('bookings', [
                'booking_group' => 'resync-draft-group',
                'living_area_id' => $area->id,
                'guest_name' => 'Updated Draft Guest',
                'status' => Booking::STATUS_DRAFT,
                'amount_cents' => 12000,
                'payment_reference' => 'venmo-update',
            ]);
        }
        $this->assertDatabaseHas('booking_activity_logs', [
            'booking_group' => 'resync-draft-group',
            'action' => 'draft_booking_updated',
            'actor_id' => $owner->id,
        ]);
        $this->assertSame(3, Mail::sent(DraftBookingUpdatedMail::class)->count());
        $this->assertDatabaseCount('notification_logs', 3);
        $this->assertDatabaseHas('notification_logs', [
            'booking_group' => 'resync-draft-group',
            'notification_type' => 'draft_updated',
            'user_id' => $admin->id,
        ]);
    }

    public function test_non_owner_and_active_owner_cannot_use_draft_update_endpoint(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $area = LivingArea::query()->firstOrFail();
        $this->createBooking($owner, $area, 'foreign-draft', Booking::STATUS_DRAFT, '2026-09-10', '2026-09-11');
        $this->createBooking($owner, $area, 'owned-active', Booking::STATUS_ACTIVE, '2026-09-15', '2026-09-16');
        $attributes = [
            'living_area_ids' => [$area->id],
            'guest_name' => 'Unauthorized edit',
            'start_date' => '2026-09-20',
            'end_date' => '2026-09-21',
            'form_context' => 'calendar-edit',
            'editing_group' => 'foreign-draft',
        ];

        $this->actingAs($otherUser)
            ->patch(route('bookings.draft.update', 'foreign-draft'), $attributes)
            ->assertForbidden();
        $this->actingAs($owner)
            ->patch(route('bookings.draft.update', 'owned-active'), $attributes)
            ->assertForbidden();
    }

    public function test_draft_update_rejects_an_active_booking_conflict(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $area = LivingArea::query()->firstOrFail();
        $this->createBooking($owner, $area, 'editable-draft', Booking::STATUS_DRAFT, '2026-09-10', '2026-09-11');
        $this->createBooking($otherUser, $area, 'blocking-active', Booking::STATUS_ACTIVE, '2026-09-20', '2026-09-22');

        $this->from(route('dashboard', ['month' => '2026-09']))
            ->actingAs($owner)
            ->patch(route('bookings.draft.update', 'editable-draft'), [
                'living_area_ids' => [$area->id],
                'guest_name' => 'Conflicting update',
                'start_date' => '2026-09-21',
                'end_date' => '2026-09-23',
                'form_context' => 'calendar-edit',
                'editing_group' => 'editable-draft',
            ])
            ->assertSessionHasErrors('living_area_ids');

        $unchangedDraft = Booking::query()->where('booking_group', 'editable-draft')->firstOrFail();
        $this->assertSame('2026-09-10', $unchangedDraft->start_date->toDateString());
        $this->assertSame('2026-09-11', $unchangedDraft->end_date->toDateString());
    }

    public function test_confirmed_calendar_edit_returns_to_its_originating_month(): void
    {
        $admin = User::factory()->create(['site_role' => 'admin']);
        $area = LivingArea::query()->firstOrFail();
        $this->createBooking($admin, $area, 'calendar-active-edit', Booking::STATUS_ACTIVE, '2027-03-10', '2027-03-11');

        $this->actingAs($admin)->patch(route('admin.bookings.update', 'calendar-active-edit'), [
            'living_area_ids' => [$area->id],
            'guest_name' => 'Updated confirmed booking',
            'start_date' => '2027-03-12',
            'end_date' => '2027-03-13',
            'payment_method' => 'pay_later',
            'return_month' => '2027-03',
            'form_context' => 'calendar-edit',
            'editing_group' => 'calendar-active-edit',
        ])->assertRedirect(route('dashboard', ['month' => '2027-03'], absolute: false));
    }

    public function test_calendar_validation_failure_reopens_the_create_context(): void
    {
        $user = User::factory()->create();

        $this->followingRedirects()
            ->actingAs($user)
            ->from(route('dashboard', ['month' => '2026-09']))
            ->post(route('bookings.store'), [
                'living_area_ids' => [],
                'guest_name' => 'Restored Guest',
                'start_date' => '2026-09-10',
                'end_date' => '',
                'form_context' => 'calendar-create',
                'return_month' => '2026-09',
            ])
            ->assertOk()
            ->assertSee('calendar-create')
            ->assertSee('Restored Guest');
    }

    public function test_calendar_validation_failure_reopens_the_edit_context(): void
    {
        $owner = User::factory()->create();
        $area = LivingArea::query()->firstOrFail();
        $this->createBooking($owner, $area, 'restore-edit-group', Booking::STATUS_DRAFT, '2026-09-10', '2026-09-11');

        $this->followingRedirects()
            ->actingAs($owner)
            ->from(route('dashboard', ['month' => '2026-09']))
            ->patch(route('bookings.draft.update', 'restore-edit-group'), [
                'living_area_ids' => [$area->id],
                'guest_name' => 'Restored Edit Guest',
                'start_date' => '2026-09-15',
                'end_date' => '',
                'form_context' => 'calendar-edit',
                'editing_group' => 'restore-edit-group',
                'return_month' => '2026-09',
            ])
            ->assertOk()
            ->assertSee('calendar-edit')
            ->assertSee('restore-edit-group')
            ->assertSee('Restored Edit Guest');
    }

    private function createBooking(
        User $creator,
        LivingArea $area,
        string $group,
        string $status,
        string $startDate,
        string $endDate,
    ): Booking {
        return Booking::query()->create([
            'booking_group' => $group,
            'living_area_id' => $area->id,
            'created_by' => $creator->id,
            'guest_name' => 'Calendar Guest',
            'start_date' => $startDate,
            'end_date' => $endDate,
            'status' => $status,
            'amount_cents' => 4000,
            'payment_status' => Booking::PAYMENT_UNPAID,
            'payment_method' => 'pay_later',
        ]);
    }
}
