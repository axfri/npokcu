<?php

namespace Tests\Feature;

use App\Mail\ProxyDeliveryMail;
use App\Models\Category;
use App\Models\Order;
use App\Models\PaymentTransaction;
use App\Models\Product;
use App\Models\ProductDurationOption;
use App\Models\ProxyDelivery;
use App\Models\User;
use App\Services\Deliveries\ProxyDeliveryService;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Contracts\Queue\ShouldBeEncrypted;
use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Mail\Attachment;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Mockery;
use RuntimeException;
use Tests\TestCase;

class ProxyDeliveryTest extends TestCase
{
    use DatabaseMigrations;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'payments.test_mode' => true,
            'deliveries.disk' => 'private',
        ]);

        Storage::fake('private');
        Storage::fake('public');
        Mail::fake();
    }

    public function test_authenticated_checkout_creates_private_file_and_queues_mail_with_same_attachment(): void
    {
        $paidAt = now()->startOfSecond();
        $this->travelTo($paidAt);
        [$product, $option] = $this->createProductWithOption(
            ['name' => 'Прокси для безопасной выдачи'],
            ['title' => '30 дней', 'duration_days' => 30],
        );
        $user = User::factory()->create(['email' => 'owner@example.test']);

        $this->checkout($user, $product, $option)->assertRedirect();

        $order = Order::query()->with('items.proxyDelivery')->sole();
        $item = $order->items->sole();
        $delivery = $item->proxyDelivery;

        $this->assertNotNull($delivery);
        $this->assertSame(PaymentTransaction::STATUS_PAID, $order->payment_status);
        $this->assertSame(Order::STATUS_COMPLETED, $order->order_status);
        $this->assertTrue($order->completed_at->equalTo($paidAt));
        $this->assertSame(ProxyDelivery::STATUS_ACTIVE, $delivery->status);
        $this->assertSame($user->getKey(), $delivery->user_id);
        $this->assertSame($order->getKey(), $delivery->order_id);
        $this->assertSame($item->getKey(), $delivery->order_item_id);
        $this->assertTrue($delivery->starts_at->equalTo($paidAt));
        $this->assertTrue($delivery->expires_at->equalTo($paidAt->copy()->addDays(30)));
        $this->assertTrue($delivery->delivered_at->equalTo($paidAt));
        $this->assertSame(0, $delivery->download_count);
        $this->assertStringStartsWith(
            $user->getKey().'/'.$order->order_number.'/',
            $delivery->file_path,
        );
        $this->assertSame($delivery->original_filename, basename($delivery->file_path));
        $this->assertStringEndsWith('.txt', $delivery->original_filename);
        $this->assertStringNotContainsString('..', $delivery->file_path);

        Storage::disk('private')->assertExists($delivery->file_path);
        $this->assertSame('hello', Storage::disk('private')->get($delivery->file_path));
        Storage::disk('public')->assertMissing($delivery->file_path);
        $this->assertDatabaseCount('proxy_deliveries', 1);

        Mail::assertQueued(
            ProxyDeliveryMail::class,
            function (ProxyDeliveryMail $mail) use ($delivery, $order, $product, $user): bool {
                $this->assertTrue($mail->delivery->is($delivery));
                $mail->assertSeeInHtml($order->order_number);
                $mail->assertSeeInHtml($product->name);
                $mail->assertSeeInHtml(route('account'));
                $mail->assertHasAttachment(
                    Attachment::fromStorageDisk('private', $delivery->file_path)
                        ->as($delivery->original_filename)
                        ->withMime('text/plain'),
                );

                return $mail->hasTo($user->email)
                    && $mail->mailer === 'smtp'
                    && $mail instanceof ShouldQueueAfterCommit
                    && $mail instanceof ShouldBeEncrypted;
            },
        );
        Mail::assertQueued(ProxyDeliveryMail::class, 1);
    }

    public function test_repeated_delivery_and_checkout_keep_one_file_record_and_mail(): void
    {
        [$product, $option] = $this->createProductWithOption();
        $user = User::factory()->create();
        $this->actingAs($user);
        $token = $this->checkoutToken($product);
        $payload = $this->checkoutPayload($option, $token, $user->email);

        $this->post(route('products.checkout.store', $product), $payload)->assertRedirect();

        $order = Order::query()->sole();
        $delivery = ProxyDelivery::query()->sole();
        $snapshot = [
            'id' => $delivery->getKey(),
            'file_path' => $delivery->file_path,
            'starts_at' => $delivery->starts_at->toISOString(),
            'expires_at' => $delivery->expires_at->toISOString(),
            'delivered_at' => $delivery->delivered_at->toISOString(),
            'download_count' => $delivery->download_count,
        ];

        app(ProxyDeliveryService::class)->deliver($order);
        $this->post(route('products.checkout.store', $product), $payload)->assertRedirect();

        $delivery->refresh();

        $this->assertSame($snapshot['id'], $delivery->getKey());
        $this->assertSame($snapshot['file_path'], $delivery->file_path);
        $this->assertSame($snapshot['starts_at'], $delivery->starts_at->toISOString());
        $this->assertSame($snapshot['expires_at'], $delivery->expires_at->toISOString());
        $this->assertSame($snapshot['delivered_at'], $delivery->delivered_at->toISOString());
        $this->assertSame($snapshot['download_count'], $delivery->download_count);
        $this->assertSame('hello', Storage::disk('private')->get($delivery->file_path));
        $this->assertDatabaseCount('orders', 1);
        $this->assertDatabaseCount('order_items', 1);
        $this->assertDatabaseCount('payment_transactions', 1);
        $this->assertDatabaseCount('proxy_deliveries', 1);
        Mail::assertQueued(ProxyDeliveryMail::class, 1);
    }

    public function test_failed_write_keeps_processing_order_and_retry_uses_same_delivery(): void
    {
        [$product, $option] = $this->createProductWithOption();
        $user = User::factory()->create();
        $order = $this->createPaidOrder($user, $product, $option);
        $failingDisk = Mockery::mock(Filesystem::class);
        $failingDisk->shouldReceive('exists')->once()->andReturnFalse();
        $failingDisk->shouldReceive('put')
            ->once()
            ->andThrow(new RuntimeException('Тестовая ошибка записи файла.'));
        Storage::set('private', $failingDisk);

        try {
            app(ProxyDeliveryService::class)->deliver($order);
        } catch (RuntimeException) {
            // Состояние неуспешной доставки проверяется ниже.
        }

        $failedDelivery = ProxyDelivery::query()->sole();

        $this->assertSame(Order::STATUS_PROCESSING, $order->fresh()->order_status);
        $this->assertNull($order->fresh()->completed_at);
        $this->assertSame(ProxyDelivery::STATUS_FAILED, $failedDelivery->status);
        $this->assertDatabaseCount('proxy_deliveries', 1);
        Mail::assertNotQueued(ProxyDeliveryMail::class);

        $failedId = $failedDelivery->getKey();
        $failedPath = $failedDelivery->file_path;
        Storage::fake('private');

        app(ProxyDeliveryService::class)->deliver($order->fresh());

        $retriedDelivery = ProxyDelivery::query()->sole();

        $this->assertSame($failedId, $retriedDelivery->getKey());
        $this->assertSame($failedPath, $retriedDelivery->file_path);
        $this->assertSame(ProxyDelivery::STATUS_ACTIVE, $retriedDelivery->status);
        $this->assertSame(Order::STATUS_COMPLETED, $order->fresh()->order_status);
        $this->assertSame('hello', Storage::disk('private')->get($retriedDelivery->file_path));
        $this->assertDatabaseCount('proxy_deliveries', 1);
        Mail::assertQueued(ProxyDeliveryMail::class, 1);
    }

    public function test_owner_can_download_exact_file_and_audit_is_recorded(): void
    {
        [$product, $option] = $this->createProductWithOption();
        $user = User::factory()->create();
        $this->checkout($user, $product, $option)->assertRedirect();
        $delivery = ProxyDelivery::query()->sole();
        $downloadedAt = now()->startOfSecond();
        $this->travelTo($downloadedAt);

        $response = $this
            ->withServerVariables([
                'REMOTE_ADDR' => '203.0.113.42',
                'HTTP_USER_AGENT' => 'ProxyDeliveryTest/1.0',
            ])
            ->get(route('account.purchases.download', $delivery));

        $response->assertOk();
        $this->assertSame('hello', $response->streamedContent());

        $delivery->refresh();
        $this->assertSame(1, $delivery->download_count);
        $this->assertTrue($delivery->last_downloaded_at->equalTo($downloadedAt));
        $this->assertDatabaseHas('download_logs', [
            'proxy_delivery_id' => $delivery->getKey(),
            'user_id' => $user->getKey(),
            'ip_address' => '203.0.113.42',
            'user_agent' => 'ProxyDeliveryTest/1.0',
        ]);
        $this->assertSame(1, $delivery->downloadLogs()->count());
    }

    public function test_other_user_gets_not_found_and_guest_is_redirected_to_login(): void
    {
        [$product, $option] = $this->createProductWithOption();
        $owner = User::factory()->create();
        $this->checkout($owner, $product, $option)->assertRedirect();
        $delivery = ProxyDelivery::query()->sole();

        $this->actingAs(User::factory()->create())
            ->get(route('account.purchases.download', $delivery))
            ->assertNotFound();

        auth()->logout();

        $this->get(route('account.purchases.download', $delivery))
            ->assertRedirect(route('login'));

        $this->assertSame(0, $delivery->fresh()->download_count);
        $this->assertDatabaseCount('download_logs', 0);
    }

    public function test_expired_delivery_cannot_be_downloaded(): void
    {
        [$product, $option] = $this->createProductWithOption();
        $user = User::factory()->create();
        $this->checkout($user, $product, $option)->assertRedirect();
        $delivery = ProxyDelivery::query()->sole();
        $delivery->update(['expires_at' => now()->subSecond()]);

        $this->get(route('account.purchases.download', $delivery))
            ->assertForbidden();

        $this->assertSame(0, $delivery->fresh()->download_count);
        $this->assertDatabaseCount('download_logs', 0);
        Storage::disk('private')->assertExists($delivery->file_path);
    }

    public function test_missing_private_file_returns_not_found_without_audit_changes(): void
    {
        [$product, $option] = $this->createProductWithOption();
        $user = User::factory()->create();
        $this->checkout($user, $product, $option)->assertRedirect();
        $delivery = ProxyDelivery::query()->sole();
        Storage::disk('private')->delete($delivery->file_path);

        $this->get(route('account.purchases.download', $delivery))
            ->assertNotFound();

        $this->assertSame(0, $delivery->fresh()->download_count);
        $this->assertNull($delivery->fresh()->last_downloaded_at);
        $this->assertDatabaseCount('download_logs', 0);
    }

    public function test_success_page_shows_owner_download_without_private_path_or_public_url(): void
    {
        [$product, $option] = $this->createProductWithOption();
        $user = User::factory()->create();
        $response = $this->checkout($user, $product, $option);
        $order = Order::query()->sole();
        $delivery = ProxyDelivery::query()->sole();

        $this->get($response->headers->get('Location'))
            ->assertOk()
            ->assertSee('Оплачено')
            ->assertSee('Файл подготовлен')
            ->assertSee('href="'.route('account.purchases.download', $delivery).'"', false)
            ->assertSee('href="'.route('account').'"', false)
            ->assertDontSee($delivery->file_path);

        $this->get('/storage/'.$delivery->file_path)->assertForbidden();
        $this->assertSame(Order::STATUS_COMPLETED, $order->order_status);
    }

    public function test_guest_success_page_is_neutral_and_never_exposes_download_or_password(): void
    {
        [$product, $option] = $this->createProductWithOption();
        $token = $this->checkoutToken($product);
        $response = $this->post(
            route('products.checkout.store', $product),
            $this->checkoutPayload($option, $token, 'guest-delivery@example.test'),
        );
        $delivery = ProxyDelivery::query()->sole();

        $this->get($response->headers->get('Location'))
            ->assertOk()
            ->assertSee('Файл отправлен на вашу почту')
            ->assertSee('Войти в аккаунт')
            ->assertDontSee(route('account.purchases.download', $delivery))
            ->assertDontSee($delivery->file_path)
            ->assertDontSee('temporaryPassword');
    }

    public function test_inactive_unsafe_and_unknown_deliveries_cannot_be_downloaded(): void
    {
        [$product, $option] = $this->createProductWithOption();
        $user = User::factory()->create();
        $this->checkout($user, $product, $option)->assertRedirect();
        $delivery = ProxyDelivery::query()->sole();

        $delivery->update([
            'status' => ProxyDelivery::STATUS_FAILED,
            'delivered_at' => null,
        ]);
        $this->get(route('account.purchases.download', $delivery))->assertForbidden();

        $delivery->update([
            'status' => ProxyDelivery::STATUS_ACTIVE,
            'delivered_at' => now(),
            'file_path' => '../secret.txt',
            'original_filename' => 'secret.txt',
        ]);
        $this->get(route('account.purchases.download', $delivery))->assertNotFound();
        $this->get('/account/purchases/999999/download')->assertNotFound();

        $this->assertSame(0, $delivery->fresh()->download_count);
        $this->assertDatabaseCount('download_logs', 0);
    }

    public function test_retry_overwrites_partial_file_and_multiple_items_receive_separate_deliveries(): void
    {
        [$product, $option] = $this->createProductWithOption();
        $user = User::factory()->create();
        $order = $this->createPaidOrder($user, $product, $option);
        $firstItem = $order->items()->sole();
        $secondItem = $order->items()->create([
            'product_id' => $product->getKey(),
            'product_name' => 'Вторая тестовая позиция',
            'quantity' => 1,
            'unit_price' => $option->price,
            'total_price' => $option->price,
            'duration_days' => $option->duration_days,
            'starts_at' => $firstItem->starts_at,
            'expires_at' => $firstItem->expires_at,
        ]);
        $filename = 'proxy-'.$order->order_number.'-PARTIAL.txt';
        $path = $user->getKey().'/'.$order->order_number.'/'.$filename;
        Storage::disk('private')->put($path, 'damaged');
        ProxyDelivery::query()->create([
            'user_id' => $user->getKey(),
            'order_id' => $order->getKey(),
            'order_item_id' => $firstItem->getKey(),
            'file_path' => $path,
            'original_filename' => $filename,
            'status' => ProxyDelivery::STATUS_FAILED,
            'starts_at' => $firstItem->starts_at,
            'expires_at' => $firstItem->expires_at,
            'download_count' => 0,
        ]);

        app(ProxyDeliveryService::class)->deliver($order);

        $deliveries = ProxyDelivery::query()->orderBy('id')->get();
        $this->assertCount(2, $deliveries);
        $this->assertSame(
            [$firstItem->getKey(), $secondItem->getKey()],
            $deliveries->pluck('order_item_id')->all(),
        );
        $this->assertCount(2, $deliveries->pluck('file_path')->unique());
        $deliveries->each(function (ProxyDelivery $delivery): void {
            $this->assertSame(ProxyDelivery::STATUS_ACTIVE, $delivery->status);
            $this->assertSame('hello', Storage::disk('private')->get($delivery->file_path));
        });
        $this->assertSame(Order::STATUS_COMPLETED, $order->fresh()->order_status);
        Mail::assertQueued(ProxyDeliveryMail::class, 2);
    }

    public function test_active_file_of_cancelled_order_cannot_be_downloaded(): void
    {
        [$product, $option] = $this->createProductWithOption();
        $user = User::factory()->create();
        $this->checkout($user, $product, $option)->assertRedirect();
        $delivery = ProxyDelivery::query()->sole();
        $delivery->order()->update([
            'order_status' => Order::STATUS_CANCELLED,
            'completed_at' => null,
        ]);

        $this->get(route('account.purchases.download', $delivery))->assertForbidden();

        $this->assertSame(0, $delivery->fresh()->download_count);
        $this->assertDatabaseCount('download_logs', 0);
        Storage::disk('private')->assertExists($delivery->file_path);
    }

    /**
     * @return array{Product, ProductDurationOption}
     */
    private function createProductWithOption(
        array $productAttributes = [],
        array $optionAttributes = [],
    ): array {
        $product = Product::factory()
            ->for(Category::factory())
            ->create($productAttributes);
        $option = ProductDurationOption::factory()
            ->for($product)
            ->create(array_merge([
                'title' => '30 дней',
                'duration_days' => 30,
                'price' => '1000.00',
            ], $optionAttributes));

        return [$product, $option];
    }

    private function checkout(
        User $user,
        Product $product,
        ProductDurationOption $option,
    ) {
        $this->actingAs($user);
        $token = $this->checkoutToken($product);

        return $this->post(
            route('products.checkout.store', $product),
            $this->checkoutPayload($option, $token, $user->email),
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

    private function createPaidOrder(
        User $user,
        Product $product,
        ProductDurationOption $option,
    ): Order {
        $paidAt = now()->startOfSecond();
        $order = Order::query()->create([
            'order_number' => 'NPK-DELIVERY-TEST',
            'user_id' => $user->getKey(),
            'customer_email' => $user->email,
            'subtotal' => $option->price,
            'total' => $option->price,
            'currency' => 'RUB',
            'payment_method' => 'test',
            'payment_status' => PaymentTransaction::STATUS_PAID,
            'order_status' => Order::STATUS_PROCESSING,
            'paid_at' => $paidAt,
        ]);

        $order->items()->create([
            'product_id' => $product->getKey(),
            'product_name' => $product->name,
            'quantity' => 1,
            'unit_price' => $option->price,
            'total_price' => $option->price,
            'duration_days' => $option->duration_days,
            'starts_at' => $paidAt,
            'expires_at' => $paidAt->copy()->addDays($option->duration_days),
        ]);

        $order->paymentTransactions()->create([
            'provider' => 'test',
            'external_payment_id' => 'test-delivery-failure',
            'amount' => $option->price,
            'currency' => 'RUB',
            'status' => PaymentTransaction::STATUS_PAID,
            'request_payload' => ['mode' => 'test'],
            'response_payload' => ['result' => 'paid'],
            'paid_at' => $paidAt,
        ]);

        return $order;
    }
}
