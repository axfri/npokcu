<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_page_is_available_to_guests(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertSee('type="email"', false)
            ->assertSee('name="remember"', false);
    }

    public function test_active_user_can_log_in(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('correct-password'),
        ]);

        $response = $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'correct-password',
        ]);

        $response->assertRedirect(route('account'));
        $this->assertAuthenticatedAs($user);
    }

    public function test_login_with_wrong_password_is_rejected(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('correct-password'),
        ]);

        $response = $this->from(route('login'))->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

        $response->assertRedirect(route('login'));
        $response->assertSessionHasErrors([
            'email' => __('auth.failed'),
        ]);
        $this->assertGuest();
    }

    public function test_blocked_user_gets_the_same_error_as_invalid_credentials(): void
    {
        $password = 'correct-password';
        $blockedUser = User::factory()->create([
            'password' => Hash::make($password),
            'status' => User::STATUS_BLOCKED,
        ]);

        $blockedResponse = $this->post(route('login.store'), [
            'email' => $blockedUser->email,
            'password' => $password,
        ]);
        $blockedResponse->assertSessionHasErrors(['email' => __('auth.failed')]);

        $unknownResponse = $this->post(route('login.store'), [
            'email' => 'unknown@example.test',
            'password' => $password,
        ]);

        $unknownResponse->assertSessionHasErrors(['email' => __('auth.failed')]);
        $this->assertGuest();
    }

    public function test_user_is_logged_out_if_account_is_blocked_after_login(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);
        $user->update(['status' => User::STATUS_BLOCKED]);

        $this->get(route('account'))
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors(['email' => __('auth.failed')]);

        $this->assertGuest();
    }

    public function test_remember_me_creates_a_persistent_login_cookie(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('correct-password'),
            'remember_token' => null,
        ]);

        $response = $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'correct-password',
            'remember' => true,
        ]);

        $response->assertRedirect(route('account'));
        $response->assertCookie(Auth::guard()->getRecallerName());
        $this->assertNotNull($user->fresh()->remember_token);
    }

    public function test_repeated_failed_login_attempts_are_rate_limited(): void
    {
        $email = 'limited@example.test';
        $throttleKey = Str::transliterate(Str::lower($email).'|127.0.0.1');
        RateLimiter::clear($throttleKey);

        User::factory()->create([
            'email' => $email,
            'password' => Hash::make('correct-password'),
        ]);

        foreach (range(1, 5) as $attempt) {
            $this->post(route('login.store'), [
                'email' => $email,
                'password' => 'wrong-password',
            ])->assertSessionHasErrors('email');
        }

        $response = $this->post(route('login.store'), [
            'email' => $email,
            'password' => 'correct-password',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
        $this->assertTrue(RateLimiter::tooManyAttempts($throttleKey, 5));
    }

    public function test_user_can_log_out_only_with_post_request(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('logout'))
            ->assertRedirect(route('home'));

        $this->assertGuest();
        $this->get('/logout')->assertMethodNotAllowed();
    }

    public function test_authenticated_user_is_redirected_away_from_login(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('login'))
            ->assertRedirect(route('account'));
    }
}
