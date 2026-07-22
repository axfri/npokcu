<?php

namespace Tests\Feature;

use App\Mail\AutoCreatedAccountMail;
use App\Mail\ExistingAccountOrderMail;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductDurationOption;
use App\Models\User;
use App\Services\Accounts\GuestAccountService;
use Illuminate\Contracts\Queue\ShouldBeEncrypted;
use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use LogicException;
use RuntimeException;
use Tests\TestCase;

class GuestAccountTest extends TestCase
{
    use DatabaseMigrations;

    protected function setUp(): void
    {
        parent::setUp();

        config(['payments.test_mode' => true]);
        Mail::fake();
    }

    public function test_guest_checkout_creates_account_and_queues_encrypted_credentials(): void
    {
        config(['mail.default' => 'log']);
        [$product, $option] = $this->createProductWithOption();

        $response = $this->checkout($product, $option, 'New.Buyer@Example.TEST');
        $response->assertRedirect();

        $order = Order::query()->sole();
        $user = User::query()->sole();
        $temporaryPassword = null;

        $this->assertSame('new.buyer@example.test', $user->email);
        $this->assertTrue($user->is_auto_created);
        $this->assertTrue($user->must_change_password);
        $this->assertSame(User::STATUS_ACTIVE, $user->status);
        $this->assertSame($user->getKey(), $order->user_id);
        $this->assertSame(Order::GUEST_ACCOUNT_CREATED, $order->guest_account_status);

        Mail::assertQueued(
            AutoCreatedAccountMail::class,
            function (AutoCreatedAccountMail $mail) use ($order, $user, &$temporaryPassword): bool {
                $temporaryPassword = $mail->temporaryPassword;
                $mail->assertSeeInHtml($order->order_number);
                $mail->assertSeeInHtml($user->email);
                $mail->assertSeeInHtml($temporaryPassword);
                $mail->assertSeeInHtml(route('login'));

                return $mail->hasTo($user->email)
                    && $mail->mailer === 'smtp'
                    && $mail instanceof ShouldQueueAfterCommit
                    && $mail instanceof ShouldBeEncrypted;
            }
        );

        $this->assertIsString($temporaryPassword);
        $this->assertTrue(Hash::check($temporaryPassword, $user->password));
        $this->assertNotSame($temporaryPassword, $user->password);
        $this->assertDatabaseMissing('users', ['password' => $temporaryPassword]);
        $this->get($response->headers->get('Location'))
            ->assertOk()
            ->assertSee('Аккаунт создан автоматически')
            ->assertDontSee($temporaryPassword);
        Mail::assertNotQueued(ExistingAccountOrderMail::class);
    }

    public function test_existing_user_is_reused_without_password_or_flag_changes(): void
    {
        $existingUser = User::factory()->create([
            'email' => 'buyer@example.test',
            'password' => Hash::make('existing-secret'),
            'is_auto_created' => false,
            'must_change_password' => false,
        ]);
        $originalPasswordHash = $existingUser->password;
        [$product, $option] = $this->createProductWithOption();

        $response = $this->checkout($product, $option, 'BUYER@EXAMPLE.TEST');
        $response->assertRedirect();

        $order = Order::query()->sole();
        $existingUser->refresh();

        $this->assertDatabaseCount('users', 1);
        $this->assertSame($existingUser->getKey(), $order->user_id);
        $this->assertSame(Order::GUEST_ACCOUNT_EXISTING, $order->guest_account_status);
        $this->assertSame($originalPasswordHash, $existingUser->password);
        $this->assertFalse($existingUser->is_auto_created);
        $this->assertFalse($existingUser->must_change_password);

        Mail::assertQueued(
            ExistingAccountOrderMail::class,
            function (ExistingAccountOrderMail $mail) use ($existingUser, $order): bool {
                $mail->assertSeeInHtml($order->order_number);
                $mail->assertSeeInHtml(route('login'));
                $mail->assertDontSeeInHtml('existing-secret');

                return $mail->hasTo($existingUser->email)
                    && $mail instanceof ShouldQueueAfterCommit
                    && $mail instanceof ShouldBeEncrypted;
            }
        );
        $this->get($response->headers->get('Location'))
            ->assertOk()
            ->assertSee('Заказ закреплён за аккаунтом с указанной почтой');
        Mail::assertNotQueued(AutoCreatedAccountMail::class);
    }

    public function test_authenticated_checkout_keeps_owner_and_sends_no_account_mail(): void
    {
        $user = User::factory()->create([
            'email' => 'owner@example.test',
            'password' => Hash::make('owner-password'),
        ]);
        $passwordHash = $user->password;
        [$product, $option] = $this->createProductWithOption();
        $this->actingAs($user);

        $token = $this->checkoutToken($product);
        $this->post(route('products.checkout.store', $product), [
            ...$this->checkoutPayload($option, $token, 'spoofed@example.test'),
        ])->assertRedirect(route('orders.success', Order::query()->sole()));

        $order = Order::query()->sole();
        $this->assertSame($user->getKey(), $order->user_id);
        $this->assertSame($user->email, $order->customer_email);
        $this->assertNull($order->guest_account_status);
        $this->assertSame($passwordHash, $user->fresh()->password);
        $this->assertDatabaseCount('users', 1);
        Mail::assertNothingQueued();
    }

    public function test_repeated_processing_does_not_replace_password_or_queue_second_mail(): void
    {
        [$product, $option] = $this->createProductWithOption();
        $token = $this->checkoutToken($product);
        $payload = $this->checkoutPayload($option, $token, 'repeat@example.test');

        $this->post(route('products.checkout.store', $product), $payload)->assertRedirect();
        $passwordHash = User::query()->sole()->password;
        $this->post(route('products.checkout.store', $product), $payload)->assertRedirect();
        app(GuestAccountService::class)->process(Order::query()->sole());

        $this->assertDatabaseCount('users', 1);
        $this->assertDatabaseCount('orders', 1);
        $this->assertSame($passwordHash, User::query()->sole()->password);
        Mail::assertQueued(AutoCreatedAccountMail::class, 1);
        Mail::assertNotQueued(ExistingAccountOrderMail::class);
    }

    public function test_email_case_variants_across_orders_use_one_user(): void
    {
        [$firstProduct, $firstOption] = $this->createProductWithOption();
        [$secondProduct, $secondOption] = $this->createProductWithOption();

        $this->checkout($firstProduct, $firstOption, 'Shared@Example.TEST')->assertRedirect();
        $this->checkout($secondProduct, $secondOption, 'shared@example.test')->assertRedirect();

        $user = User::query()->sole();
        $this->assertDatabaseCount('users', 1);
        $this->assertDatabaseCount('orders', 2);
        $this->assertSame(
            [$user->getKey()],
            Order::query()->distinct()->pluck('user_id')->all(),
        );
        Mail::assertQueued(AutoCreatedAccountMail::class, 1);
        Mail::assertQueued(ExistingAccountOrderMail::class, 1);
    }

    public function test_unpaid_order_cannot_create_guest_account(): void
    {
        $order = Order::query()->create([
            'order_number' => 'NPK-UNPAID',
            'guest_account_status' => Order::GUEST_ACCOUNT_PENDING,
            'customer_email' => 'unpaid@example.test',
            'subtotal' => '100.00',
            'total' => '100.00',
            'currency' => 'RUB',
            'payment_status' => 'pending',
            'order_status' => Order::STATUS_PENDING,
        ]);

        try {
            app(GuestAccountService::class)->process($order);
            $this->fail('Ожидался отказ в обработке неоплаченного заказа.');
        } catch (RuntimeException $exception) {
            $this->assertSame(
                'Аккаунт можно привязать только к оплаченному заказу.',
                $exception->getMessage(),
            );
        }

        $this->assertDatabaseCount('users', 0);
        $this->assertNull($order->fresh()->user_id);
        Mail::assertNothingQueued();
    }

    public function test_unsafe_credentials_mailer_rolls_back_checkout(): void
    {
        config(['mail.credentials_mailer' => 'log']);
        [$product, $option] = $this->createProductWithOption();
        $this->withoutExceptionHandling();

        try {
            $this->checkout($product, $option, 'unsafe-mailer@example.test');
            $this->fail('Checkout must reject an unsafe credentials mailer.');
        } catch (LogicException $exception) {
            $this->assertSame(
                'Почтовый транспорт для временных паролей настроен небезопасно.',
                $exception->getMessage(),
            );
        }

        $this->assertDatabaseCount('users', 0);
        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseCount('payment_transactions', 0);
        Mail::assertNothingQueued();
    }

    /**
     * @return array{Product, ProductDurationOption}
     */
    private function createProductWithOption(): array
    {
        $product = Product::factory()
            ->for(Category::factory())
            ->create();
        $option = ProductDurationOption::factory()
            ->for($product)
            ->create();

        return [$product, $option];
    }

    private function checkout(Product $product, ProductDurationOption $option, string $email)
    {
        $token = $this->checkoutToken($product);

        return $this->post(
            route('products.checkout.store', $product),
            $this->checkoutPayload($option, $token, $email),
        );
    }

    private function checkoutToken(Product $product): string
    {
        $response = $this->get(route('products.checkout', $product));
        $response->assertOk();
        preg_match(
            '/name="checkout_token" value="([A-Za-z0-9]{64})"/',
            $response->getContent(),
            $matches,
        );

        return $matches[1];
    }

    private function checkoutPayload(
        ProductDurationOption $option,
        string $token,
        string $email,
    ): array {
        return [
            'duration_option_id' => $option->getKey(),
            'email' => $email,
            'payment_method' => 'test',
            'terms' => '1',
            'checkout_token' => $token,
        ];
    }
}
