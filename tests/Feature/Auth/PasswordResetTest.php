<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_password_reset_link_page_is_available(): void
    {
        $this->get(route('password.request'))
            ->assertOk()
            ->assertSee('type="email"', false);
    }

    public function test_password_reset_link_can_be_requested(): void
    {
        Notification::fake();
        $user = User::factory()->create();

        $this->post(route('password.email'), ['email' => $user->email])
            ->assertSessionHasNoErrors()
            ->assertSessionHas('status');

        Notification::assertSentTo($user, ResetPassword::class);
    }

    public function test_password_can_be_reset_with_a_valid_token(): void
    {
        Notification::fake();
        $user = User::factory()->create([
            'password' => Hash::make('old-password'),
        ]);

        $this->post(route('password.email'), ['email' => $user->email]);

        Notification::assertSentTo(
            $user,
            ResetPassword::class,
            function (ResetPassword $notification) use ($user): bool {
                $response = $this->post(route('password.update'), [
                    'token' => $notification->token,
                    'email' => $user->email,
                    'password' => 'new-secure-password',
                    'password_confirmation' => 'new-secure-password',
                ]);

                $response->assertSessionHasNoErrors();
                $response->assertRedirect(route('login'));

                return true;
            },
        );

        $user->refresh();
        $this->assertTrue(Hash::check('new-secure-password', $user->password));
        $this->assertFalse(Hash::check('old-password', $user->password));
    }

    public function test_invalid_password_reset_token_is_rejected(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('old-password'),
        ]);

        $response = $this->post(route('password.update'), [
            'token' => 'invalid-token',
            'email' => $user->email,
            'password' => 'new-secure-password',
            'password_confirmation' => 'new-secure-password',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertTrue(Hash::check('old-password', $user->fresh()->password));
        $this->assertFalse(Hash::check('new-secure-password', $user->fresh()->password));
    }

    public function test_reset_password_page_accepts_token_and_email(): void
    {
        $this->get(route('password.reset', [
            'token' => 'test-token',
            'email' => 'user@example.test',
        ]))
            ->assertOk()
            ->assertSee('value="test-token"', false)
            ->assertSee('value="user@example.test"', false);
    }
}
