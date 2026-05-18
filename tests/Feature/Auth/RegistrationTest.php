<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_screen_can_be_rendered(): void
    {
        $response = $this->get('/register');

        $response->assertStatus(200);
    }

    public function test_new_users_can_register(): void
    {
        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $this->assertGuest();
        $response->assertRedirect(route('approval.pending', absolute: false));

        $this->assertDatabaseHas('users', [
            'email' => 'test@example.com',
            'site_role' => 'standard',
        ]);
        $this->assertDatabaseHas('users', [
            'email' => 'test@example.com',
            'approved_at' => null,
        ]);
    }
}
