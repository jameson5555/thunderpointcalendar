<?php

namespace Tests\Feature;

use App\Models\LivingArea;
use App\Models\User;
use Database\Seeders\LivingAreaSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NavigationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(LivingAreaSeeder::class);
    }

    public function test_member_navigation_identifies_the_calendar_and_profile_as_current(): void
    {
        $member = User::factory()->create();

        $dashboard = $this->actingAs($member)->get(route('dashboard'));

        $dashboard->assertOk()
            ->assertSee('Thunderpoint calendar')
            ->assertSee('aria-current="page"', false)
            ->assertSee('Add Thunderpoint to your phone')
            ->assertDontSee('Manage');
        $this->assertSame(2, substr_count($dashboard->getContent(), 'aria-current="page"'));

        $profile = $this->actingAs($member)->get(route('profile.edit'));

        $profile->assertOk()
            ->assertSee('aria-current="page"', false);
        $this->assertSame(2, substr_count($profile->getContent(), 'aria-current="page"'));
    }

    public function test_manage_navigation_is_available_to_admins_and_poobahs(): void
    {
        $admin = User::factory()->create(['site_role' => 'admin']);
        $poobah = User::factory()->create();
        $poobah->managedAreas()->attach(LivingArea::query()->firstOrFail()->id, ['role' => 'poobah']);

        foreach ([$admin, $poobah] as $manager) {
            $response = $this->actingAs($manager)->get(route('admin.index'));

            $response->assertOk()
                ->assertSee('Manage')
                ->assertSee('aria-current="page"', false);
            $this->assertSame(2, substr_count($response->getContent(), 'aria-current="page"'));
        }
    }

    public function test_every_layout_links_the_installable_web_app_manifest(): void
    {
        $member = User::factory()->create();

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('rel="manifest"', false)
            ->assertSee('site.webmanifest');

        $this->actingAs($member)->get(route('dashboard'))
            ->assertOk()
            ->assertSee('rel="manifest"', false)
            ->assertSee('site.webmanifest');
    }

    public function test_staging_banner_is_only_rendered_in_the_staging_environment(): void
    {
        $member = User::factory()->create();

        $this->get(route('home'))->assertDontSee('Staging environment');

        config(['app.env' => 'staging']);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Staging environment');

        $this->actingAs($member)->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Staging environment');
    }

    public function test_web_app_manifest_has_the_expected_identity_and_icons(): void
    {
        $manifest = json_decode(file_get_contents(public_path('site.webmanifest')), true, flags: JSON_THROW_ON_ERROR);

        $this->assertSame('Thunderpoint', $manifest['name']);
        $this->assertSame('Thunderpoint', $manifest['short_name']);
        $this->assertSame('/dashboard', $manifest['start_url']);
        $this->assertSame('/', $manifest['scope']);
        $this->assertSame('standalone', $manifest['display']);
        $this->assertSame(['192x192', '512x512'], array_column($manifest['icons'], 'sizes'));

        foreach ($manifest['icons'] as $icon) {
            $this->assertFileExists(public_path(ltrim($icon['src'], '/')));
        }

        $this->get('/dashboard')->assertRedirect(route('login'));
    }
}
