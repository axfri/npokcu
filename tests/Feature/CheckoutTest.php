<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\PaymentTransaction;
use App\Models\Product;
use App\Models\ProductDurationOption;
use App\Models\User;
use App\Services\Payments\TestPaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use RuntimeException;
use Tests\TestCase;

class CheckoutTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['payments.test_mode' => true]);
        Mail::fake();
    }

    public function test_checkout_form_shows_active_options_and_prefills_authenticated_email(): void
    {
        [$product, $option] = $this->createProductWithOption([], ['duration_days' => 30]);
        ProductDurationOption::factory()->for($product)->create([
            'title' => 'Скрытый срок',
            'duration_days' => 31,
            'is_active' => false,
        ]);
        $user = User::factory()->create(['email' => 'buyer@example.test']);

        $this->actingAs($user)
            ->get(route('products.checkout', $product))
            ->assertOk()
            ->assertSee($option->title)
            ->assertDontSee('Скрытый срок')
            ->assertSee('buyer@example.test')
            ->assertSee('name="_token"', false)
            ->assertSee('name="checkout_token"', false)
            ->assertDontSee('name="price"', false)
            ->assertDontSee('name="total"', false);
    }

    public function test_authenticated_user_can_checkout_and_server_uses_account_email(): void
    {
        [$product, $option] = $this->createProductWithOption();
        $user = User::factory()->create(['email' => 'owner@example.test']);
        $this->actingAs($user);
        $token = $this->checkoutToken($product);

        $response = $this->post(
            route('products.checkout.store', $product),
            $this->checkoutPayload($option, $token, ['email' => 'spoofed@example.test'])
        );

        $order = Order::query()->sole();
        $response->assertRedirect(route('orders.success', $order));
        $this->assertSame($user->getKey(), $order->user_id);
        $this->assertSame($user->email, $order->customer_email);
        $this->assertMatchesRegularExpression('/^NPK-\d{8}-[A-Z0-9]{6}$/', $order->order_number);
        $this->assertNotSame((string) $order->getKey(), $order->order_number);
    }

    public function test_guest_can_checkout_with_email_and_receives_signed_success_url(): void
    {
        [$product, $option] = $this->createProductWithOption();
        $token = $this->checkoutToken($product);

        $response = $this->post(
            route('products.checkout.store', $product),
            $this->checkoutPayload($option, $token, ['email' => 'guest@example.test'])
        );

        $order = Order::query()->sole();
        $this->assertNotNull($order->user_id);
        $this->assertSame(Order::GUEST_ACCOUNT_CREATED, $order->guest_account_status);
        $this->assertSame('guest@example.test', $order->user->email);
        $this->assertSame('guest@example.test', $order->customer_email);

        $successUrl = $response->headers->get('Location');
        $this->assertNotNull($successUrl);
        $this->assertStringContainsString('signature=', $successUrl);
        $this->get($successUrl)
            ->assertOk()
            ->assertSee($order->order_number)
            ->assertSee('guest@example.test');
    }

    public function test_order_item_is_a_server_side_snapshot_and_ignores_tampered_values(): void
    {
        [$product, $option] = $this->createProductWithOption([
            'name' => 'Прокси для теста',
            'base_price' => '100.00',
        ], [
            'title' => '60 дней',
            'duration_days' => 60,
            'price' => '1499.50',
        ]);
        $token = $this->checkoutToken($product);

        $this->post(
            route('products.checkout.store', $product),
            $this->checkoutPayload($option, $token, [
                'price' => '1.00',
                'total' => '1.00',
                'product_name' => 'Подменённый товар',
                'duration_days' => 999,
                'user_id' => 999,
                'order_status' => Order::STATUS_COMPLETED,
            ])
        )->assertRedirect();

        $order = Order::query()->sole();
        $item = OrderItem::query()->sole();

        $this->assertSame('1499.50', $order->subtotal);
        $this->assertSame('1499.50', $order->total);
        $this->assertSame('Прокси для теста', $item->product_name);
        $this->assertSame(1, $item->quantity);
        $this->assertSame('1499.50', $item->unit_price);
        $this->assertSame('1499.50', $item->total_price);
        $this->assertSame(60, $item->duration_days);
        $this->assertNotSame(999, $order->user_id);
        $this->assertSame('guest@example.test', $order->user->email);
        $this->assertSame(Order::STATUS_COMPLETED, $order->order_status);
    }

    public function test_duration_option_must_belong_to_selected_product_and_be_active(): void
    {
        [$product] = $this->createProductWithOption();
        [$otherProduct, $otherOption] = $this->createProductWithOption();
        $token = $this->checkoutToken($product);

        $this->from(route('products.checkout', $product))
            ->post(
                route('products.checkout.store', $product),
                $this->checkoutPayload($otherOption, $token)
            )
            ->assertRedirect(route('products.checkout', $product))
            ->assertSessionHasErrors('duration_option_id');

        $inactiveOption = ProductDurationOption::factory()->for($product)->create([
            'is_active' => false,
        ]);
        $secondToken = $this->checkoutToken($product);

        $this->post(
            route('products.checkout.store', $product),
            $this->checkoutPayload($inactiveOption, $secondToken)
        )->assertSessionHasErrors('duration_option_id');

        $this->assertDatabaseCount('orders', 0);
        $this->assertNotSame($product->getKey(), $otherProduct->getKey());
    }

    public function test_inactive_product_and_product_of_inactive_category_cannot_be_bought(): void
    {
        [$product, $option] = $this->createProductWithOption();
        $token = $this->checkoutToken($product);
        $product->update(['is_active' => false]);

        $this->post(
            route('products.checkout.store', $product),
            $this->checkoutPayload($option, $token)
        )->assertNotFound();

        [$hiddenCategoryProduct] = $this->createProductWithOption();
        $hiddenCategoryProduct->category->update(['is_active' => false]);

        $this->get(route('products.checkout', $hiddenCategoryProduct))->assertNotFound();
        $this->assertDatabaseCount('orders', 0);
    }

    public function test_blocked_authenticated_user_cannot_open_checkout(): void
    {
        [$product] = $this->createProductWithOption();
        $blockedUser = User::factory()->create(['status' => User::STATUS_BLOCKED]);

        $this->actingAs($blockedUser)
            ->get(route('products.checkout', $product))
            ->assertRedirect(route('login'));

        $this->assertGuest();
        $this->assertDatabaseCount('orders', 0);
    }

    public function test_checkout_requires_valid_email_terms_and_test_payment_method(): void
    {
        [$product, $option] = $this->createProductWithOption();
        $token = $this->checkoutToken($product);

        $this->post(route('products.checkout.store', $product), [
            'duration_option_id' => $option->getKey(),
            'checkout_token' => $token,
            'email' => 'wrong-email',
            'payment_method' => 'real-provider',
        ])->assertSessionHasErrors(['email', 'payment_method', 'terms']);

        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseCount('order_items', 0);
        $this->assertDatabaseCount('payment_transactions', 0);
        $this->assertDatabaseCount('users', 0);
    }

    public function test_test_payment_marks_order_and_payment_paid_and_sets_duration_dates(): void
    {
        [$product, $option] = $this->createProductWithOption([], [
            'duration_days' => 30,
            'price' => '750.00',
        ]);
        $paidAt = now()->startOfSecond();
        $this->travelTo($paidAt);
        $token = $this->checkoutToken($product);

        $this->post(
            route('products.checkout.store', $product),
            $this->checkoutPayload($option, $token)
        )->assertRedirect();

        $order = Order::query()->sole();
        $item = OrderItem::query()->sole();
        $payment = PaymentTransaction::query()->sole();

        $this->assertSame('test', $order->payment_method);
        $this->assertSame(PaymentTransaction::STATUS_PAID, $order->payment_status);
        $this->assertSame(Order::STATUS_COMPLETED, $order->order_status);
        $this->assertTrue($order->paid_at->equalTo($paidAt));
        $this->assertSame('test', $payment->provider);
        $this->assertSame(PaymentTransaction::STATUS_PAID, $payment->status);
        $this->assertSame('750.00', $payment->amount);
        $this->assertTrue($payment->paid_at->equalTo($paidAt));
        $this->assertTrue($item->starts_at->equalTo($paidAt));
        $this->assertTrue($item->expires_at->equalTo($paidAt->copy()->addDays(30)));
    }

    public function test_repeated_submission_and_payment_do_not_create_duplicates(): void
    {
        [$product, $option] = $this->createProductWithOption();
        $token = $this->checkoutToken($product);
        $payload = $this->checkoutPayload($option, $token);

        $this->post(route('products.checkout.store', $product), $payload)->assertRedirect();
        $this->post(route('products.checkout.store', $product), $payload)->assertRedirect();

        $order = Order::query()->sole();
        $originalPayment = PaymentTransaction::query()->sole();
        $originalPaidAt = $originalPayment->paid_at;

        $repeatedPayment = app(TestPaymentService::class)->pay($order);

        $this->assertSame($originalPayment->getKey(), $repeatedPayment->getKey());
        $this->assertTrue($repeatedPayment->paid_at->equalTo($originalPaidAt));
        $this->assertDatabaseCount('orders', 1);
        $this->assertDatabaseCount('order_items', 1);
        $this->assertDatabaseCount('payment_transactions', 1);
    }

    public function test_failed_payment_rolls_back_order_and_item(): void
    {
        [$product, $option] = $this->createProductWithOption();
        $token = $this->checkoutToken($product);
        $this->mock(TestPaymentService::class, function ($mock): void {
            $mock->shouldReceive('pay')
                ->once()
                ->andThrow(new RuntimeException('Тестовая ошибка оплаты.'));
        });
        $this->withoutExceptionHandling();

        try {
            $this->post(
                route('products.checkout.store', $product),
                $this->checkoutPayload($option, $token)
            );
            $this->fail('Ожидалось исключение тестовой оплаты.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Тестовая ошибка оплаты.', $exception->getMessage());
        }

        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseCount('order_items', 0);
        $this->assertDatabaseCount('payment_transactions', 0);
    }

    public function test_disabled_test_mode_does_not_create_order(): void
    {
        [$product, $option] = $this->createProductWithOption();
        $token = $this->checkoutToken($product);
        config(['payments.test_mode' => false]);

        $this->get(route('products.checkout', $product))
            ->assertOk()
            ->assertSee('Недоступно')
            ->assertSee('disabled', false);

        $this->post(
            route('products.checkout.store', $product),
            $this->checkoutPayload($option, $token)
        )->assertSessionHasErrors('payment_method');

        $this->assertDatabaseCount('orders', 0);
    }

    public function test_authenticated_user_can_open_only_their_own_order(): void
    {
        [$product, $option] = $this->createProductWithOption();
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $this->actingAs($owner);
        $token = $this->checkoutToken($product);
        $this->post(
            route('products.checkout.store', $product),
            $this->checkoutPayload($option, $token)
        )->assertRedirect();
        $order = Order::query()->sole();

        $this->get(route('orders.success', $order))
            ->assertOk()
            ->assertSee($order->order_number);

        $this->actingAs($otherUser)
            ->get(route('orders.success', $order))
            ->assertNotFound();

        $signedUrl = URL::temporarySignedRoute(
            'orders.success',
            now()->addHour(),
            ['order' => $order]
        );
        $this->get($signedUrl)->assertNotFound();
    }

    public function test_guest_order_is_available_only_through_valid_signed_url_and_refresh_is_safe(): void
    {
        [$product, $option] = $this->createProductWithOption();
        $token = $this->checkoutToken($product);
        $response = $this->post(
            route('products.checkout.store', $product),
            $this->checkoutPayload($option, $token)
        );
        $order = Order::query()->sole();
        $signedUrl = $response->headers->get('Location');

        $this->get(route('orders.success', $order))->assertNotFound();
        $this->get($signedUrl.'&signature=invalid')->assertNotFound();
        $expiredUrl = URL::temporarySignedRoute(
            'orders.success',
            now()->subMinute(),
            ['order' => $order]
        );
        $this->get($expiredUrl)->assertNotFound();
        $this->get($signedUrl)->assertOk();
        $this->get($signedUrl)->assertOk();

        $this->actingAs(User::factory()->create())
            ->get($signedUrl)
            ->assertNotFound();

        $this->assertDatabaseCount('orders', 1);
        $this->assertDatabaseCount('order_items', 1);
        $this->assertDatabaseCount('payment_transactions', 1);
    }

    public function test_success_page_escapes_saved_product_name(): void
    {
        [$product, $option] = $this->createProductWithOption([
            'name' => '<script>alert("order")</script>',
        ]);
        $token = $this->checkoutToken($product);
        $response = $this->post(
            route('products.checkout.store', $product),
            $this->checkoutPayload($option, $token)
        );

        $this->get($response->headers->get('Location'))
            ->assertOk()
            ->assertSee('&lt;script&gt;alert(&quot;order&quot;)&lt;/script&gt;', false)
            ->assertDontSee('<script>alert("order")</script>', false);
    }

    /**
     * @return array{Product, ProductDurationOption}
     */
    private function createProductWithOption(array $productAttributes = [], array $optionAttributes = []): array
    {
        $category = Category::factory()->create();
        $product = Product::factory()
            ->for($category)
            ->create($productAttributes);
        $option = ProductDurationOption::factory()
            ->for($product)
            ->create($optionAttributes);

        return [$product, $option];
    }

    private function checkoutToken(Product $product): string
    {
        $response = $this->get(route('products.checkout', $product));
        $response->assertOk();

        $matched = preg_match(
            '/name="checkout_token" value="([A-Za-z0-9]{64})"/',
            $response->getContent(),
            $matches
        );

        $this->assertSame(1, $matched, 'Токен оформления не найден в форме.');

        return $matches[1];
    }

    private function checkoutPayload(
        ProductDurationOption $option,
        string $checkoutToken,
        array $overrides = [],
    ): array {
        return array_merge([
            'duration_option_id' => $option->getKey(),
            'email' => 'guest@example.test',
            'payment_method' => 'test',
            'terms' => '1',
            'checkout_token' => $checkoutToken,
        ], $overrides);
    }
}
