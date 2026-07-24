<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccountTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_open_account_page(): void
    {
        $this->get(route('account'))
            ->assertRedirect(route('login'));
    }

    public function test_authenticated_user_can_open_account_page(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('account'))
            ->assertOk()
            ->assertSee($user->email)
            ->assertSee($user->created_at->format('d.m.Y'))
            ->assertSee('У вас пока нет купленных товаров.')
            ->assertSee('action="'.route('logout').'"', false);
    }

    public function test_unverified_user_can_open_account_page(): void
    {
        $user = User::factory()->unverified()->create();

        $this->actingAs($user)
            ->get(route('account'))
            ->assertOk()
            ->assertSee($user->email)
            ->assertSee('Email не подтверждён');
    }

    public function test_account_page_escapes_user_email(): void
    {
        $user = User::factory()->create([
            'email' => 'customer&support@example.test',
        ]);

        $this->actingAs($user)
            ->get(route('account'))
            ->assertOk()
            ->assertSee('customer&amp;support@example.test', false)
            ->assertDontSee('customer&support@example.test', false);
    }
}
