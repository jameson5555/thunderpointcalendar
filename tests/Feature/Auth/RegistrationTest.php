<?php

namespace Tests\Feature\Auth;

use App\Mail\UserRegistrationPendingApprovalMail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_screen_can_be_rendered(): void
    {
        $response = $this->get('/register');

        $response->assertRedirect(route('home', ['auth' => 'register'], absolute: false));
    }

    public function test_new_users_can_register(): void
    {
        Mail::fake();

        $approvedAdmin = User::factory()->create([
            'site_role' => 'admin',
            'email' => 'admin@example.com',
        ]);
        User::factory()->pendingApproval()->create([
            'site_role' => 'admin',
            'email' => 'pending-admin@example.com',
        ]);

        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $this->assertGuest();
        $response->assertRedirect(route('approval.pending', absolute: false));

        $this->get(route('approval.pending'))
            ->assertOk()
            ->assertSee('data-flash-toast-region', false)
            ->assertSee('Your account has been created and is waiting for approval.')
            ->assertSee('Return home')
            ->assertDontSee('Back to sign in');

        $this->assertDatabaseHas('users', [
            'email' => 'test@example.com',
            'site_role' => 'standard',
        ]);
        $this->assertDatabaseHas('users', [
            'email' => 'test@example.com',
            'approved_at' => null,
        ]);

        Mail::assertSent(UserRegistrationPendingApprovalMail::class, 1);
        Mail::assertSent(UserRegistrationPendingApprovalMail::class, fn (UserRegistrationPendingApprovalMail $mail) => $mail->hasTo($approvedAdmin->email));
        Mail::assertNotSent(UserRegistrationPendingApprovalMail::class, fn (UserRegistrationPendingApprovalMail $mail) => $mail->hasTo('pending-admin@example.com'));
    }
}
