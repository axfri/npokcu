<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ChangePasswordTest extends TestCase
{
    use RefreshDatabase;

    public function test_temporary_user_is_redirected_to_password_change_after_login(): void
    {
        $user = User::factory()->unverified()->create([
            'password' => Hash::make('temporary-password'),
            'is_auto_created' => true,
            'must_change_password' => true,
        ]);

        $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'temporary-password',
        ])->assertRedirect(route('account.password.edit'));

        $this->assertAuthenticatedAs($user);
    }

    public function test_temporary_user_can_open_change_form_but_not_account(): void
    {
        $user = User::factory()->unverified()->create([
            'must_change_password' => true,
        ]);

        $this->actingAs($user)
            ->get(route('account'))
            ->assertRedirect(route('account.password.edit'));

        $this->get(route('account.password.edit'))
            ->assertOk()
            ->assertSee('name="_token"', false)
            ->assertSee('name="password_confirmation"', false)
            ->assertSee('action="'.route('logout').'"', false);

        $this->get(route('verification.notice'))->assertOk();
    }

    public function test_temporary_user_can_set_new_password_and_open_account(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('temporary-password'),
            'is_auto_created' => true,
            'must_change_password' => true,
            'remember_token' => 'old-remember-token',
        ]);

        $this->actingAs($user)
            ->put(route('account.password.update'), [
                'password' => 'new-secure-password',
                'password_confirmation' => 'new-secure-password',
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('account'));

        $user->refresh();
        $this->assertFalse($user->must_change_password);
        $this->assertTrue($user->is_auto_created);
        $this->assertTrue(Hash::check('new-secure-password', $user->password));
        $this->assertFalse(Hash::check('temporary-password', $user->password));
        $this->assertNotSame('old-remember-token', $user->remember_token);
        $this->assertAuthenticatedAs($user);

        $this->get(route('account'))->assertOk();
    }

    public function test_guest_cannot_open_or_submit_password_change(): void
    {
        $this->get(route('account.password.edit'))
            ->assertRedirect(route('login'));

        $this->put(route('account.password.update'), [
            'password' => 'new-secure-password',
            'password_confirmation' => 'new-secure-password',
        ])->assertRedirect(route('login'));
    }

    public function test_regular_user_cannot_use_temporary_password_change_flow(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('current-password'),
            'must_change_password' => false,
        ]);
        $originalHash = $user->password;

        $this->actingAs($user)
            ->get(route('account.password.edit'))
            ->assertRedirect(route('account'));

        $this->put(route('account.password.update'), [
            'password' => 'replacement-password',
            'password_confirmation' => 'replacement-password',
        ])->assertForbidden();

        $this->assertSame($originalHash, $user->fresh()->password);
    }

    public function test_password_confirmation_validation_keeps_temporary_password(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('temporary-password'),
            'must_change_password' => true,
        ]);
        $originalHash = $user->password;

        $this->actingAs($user)
            ->from(route('account.password.edit'))
            ->put(route('account.password.update'), [
                'password' => 'new-secure-password',
                'password_confirmation' => 'different-password',
            ])
            ->assertRedirect(route('account.password.edit'))
            ->assertSessionHasErrors('password');

        $user->refresh();
        $this->assertTrue($user->must_change_password);
        $this->assertSame($originalHash, $user->password);
    }

    public function test_temporary_password_cannot_be_reused_as_new_password(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('temporary-password'),
            'must_change_password' => true,
        ]);
        $originalHash = $user->password;

        $this->actingAs($user)
            ->from(route('account.password.edit'))
            ->put(route('account.password.update'), [
                'password' => 'temporary-password',
                'password_confirmation' => 'temporary-password',
            ])
            ->assertRedirect(route('account.password.edit'))
            ->assertSessionHasErrors('password');

        $user->refresh();
        $this->assertTrue($user->must_change_password);
        $this->assertSame($originalHash, $user->password);
    }

    public function test_changing_temporary_password_deletes_other_database_sessions(): void
    {
        config(['session.driver' => 'database']);
        $user = User::factory()->create([
            'password' => Hash::make('temporary-password'),
            'must_change_password' => true,
        ]);

        DB::table('sessions')->insert([
            'id' => 'other-device-session',
            'user_id' => $user->getKey(),
            'ip_address' => '127.0.0.1',
            'user_agent' => 'Feature test',
            'payload' => base64_encode(serialize([])),
            'last_activity' => now()->timestamp,
        ]);

        $this->actingAs($user)
            ->put(route('account.password.update'), [
                'password' => 'new-secure-password',
                'password_confirmation' => 'new-secure-password',
            ])
            ->assertRedirect(route('account'));

        $this->assertDatabaseMissing('sessions', ['id' => 'other-device-session']);
    }

    public function test_temporary_user_can_log_out_without_changing_password(): void
    {
        $user = User::factory()->create(['must_change_password' => true]);

        $this->actingAs($user)
            ->post(route('logout'))
            ->assertRedirect(route('home'));

        $this->assertGuest();
    }
}
