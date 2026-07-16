<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_page_is_available_to_guests(): void
    {
        $this->get(route('register'))
            ->assertOk()
            ->assertSee('type="email"', false)
            ->assertSee('name="password_confirmation"', false);
    }

    public function test_guest_can_register_with_email_and_is_authenticated(): void
    {
        $response = $this->post(route('register.store'), [
            'email' => 'new-user@example.test',
            'password' => 'Secure-password-123',
            'password_confirmation' => 'Secure-password-123',
        ]);

        $response->assertRedirect(route('account'));
        $this->assertAuthenticated();

        $user = User::query()->where('email', 'new-user@example.test')->sole();

        $this->assertAuthenticatedAs($user);
        $this->assertFalse($user->is_auto_created);
        $this->assertFalse($user->must_change_password);
        $this->assertSame(User::STATUS_ACTIVE, $user->status);
        $this->assertTrue(Hash::check('Secure-password-123', $user->password));
        $this->assertNotSame('Secure-password-123', $user->password);
    }

    public function test_email_must_be_unique_during_registration(): void
    {
        User::factory()->create(['email' => 'existing@example.test']);

        $response = $this->from(route('register'))->post(route('register.store'), [
            'email' => 'existing@example.test',
            'password' => 'Secure-password-123',
            'password_confirmation' => 'Secure-password-123',
        ]);

        $response->assertRedirect(route('register'));
        $response->assertSessionHasErrors('email');
        $this->assertGuest();
        $this->assertSame(1, User::query()->where('email', 'existing@example.test')->count());
    }

    public function test_password_confirmation_is_required(): void
    {
        $response = $this->post(route('register.store'), [
            'email' => 'new-user@example.test',
            'password' => 'Secure-password-123',
            'password_confirmation' => 'another-password',
        ]);

        $response->assertSessionHasErrors('password');
        $this->assertGuest();
        $this->assertDatabaseMissing('users', ['email' => 'new-user@example.test']);
    }

    public function test_authenticated_user_is_redirected_away_from_registration(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('register'))
            ->assertRedirect(route('account'));
    }
}
