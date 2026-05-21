<?php

namespace Tests\Feature;

use App\Models\LivingArea;
use App\Models\User;
use Database\Seeders\LivingAreaSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminUserApprovalTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(LivingAreaSeeder::class);
    }

    public function test_admin_screen_shows_pending_account_approvals(): void
    {
        $admin = User::factory()->create(['site_role' => 'admin']);
        $pendingUser = User::factory()->pendingApproval()->create([
            'name' => 'Pending Person',
            'email' => 'pending@example.com',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.index'))
            ->assertOk()
            ->assertSee('Pending account approvals')
            ->assertSee($pendingUser->name)
            ->assertSee($pendingUser->email)
            ->assertSee('Approve User');
    }

    public function test_admin_can_approve_a_pending_user(): void
    {
        $admin = User::factory()->create(['site_role' => 'admin']);
        $pendingUser = User::factory()->pendingApproval()->create([
            'name' => 'Needs Approval',
        ]);

        $this->actingAs($admin)
            ->patch(route('admin.users.approve', $pendingUser))
            ->assertRedirect(route('admin.index', absolute: false));

        $this->assertNotNull($pendingUser->fresh()->approved_at);

        $this->actingAs($admin)
            ->get(route('admin.index'))
            ->assertOk()
            ->assertSee('No pending users right now.');
    }

    public function test_non_admin_users_cannot_approve_pending_users(): void
    {
        $poobah = User::factory()->create();
        $managedArea = LivingArea::query()->firstOrFail();
        $poobah->managedAreas()->attach($managedArea->id, ['role' => 'poobah']);
        $pendingUser = User::factory()->pendingApproval()->create();

        $this->actingAs($poobah)
            ->patch(route('admin.users.approve', $pendingUser))
            ->assertForbidden();

        $this->assertNull($pendingUser->fresh()->approved_at);
    }
}