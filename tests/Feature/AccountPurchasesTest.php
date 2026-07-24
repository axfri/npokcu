<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\PaymentTransaction;
use App\Models\Product;
use App\Models\ProxyDelivery;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AccountPurchasesTest extends TestCase
{
    use RefreshDatabase;

    private int $sequence = 0;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('private');
        $this->travelTo(Carbon::parse('2026-07-24 12:00:00'));
    }

    public function test_empty_account_shows_empty_state_and_catalog_link(): void
    {
        $user = $this->createUser('empty@example.test');

        $this->actingAs($user)
            ->get(route('account'))
            ->assertOk()
            ->assertSee('У вас пока нет купленных товаров.')
            ->assertSee('href="'.route('catalog').'"', false);
    }

    public function test_active_paid_purchase_is_shown_with_dates_status_and_download_link(): void
    {
        $user = $this->createUser('owner@example.test');
        [$order, $item, $delivery] = $this->createPurchase(
            $user,
            [],
            [
                'starts_at' => now()->subDays(5),
                'expires_at' => now()->addDays(5),
                'download_count' => 2,
            ],
            [
                'product_name' => 'Прокси для активной покупки',
                'duration_days' => 10,
                'starts_at' => now()->subDays(5),
                'expires_at' => now()->addDays(5),
            ],
        );

        $this->actingAs($user)
            ->get(route('account'))
            ->assertOk()
            ->assertSee($order->order_number)
            ->assertSee($item->product_name)
            ->assertSee('Активен')
            ->assertSee($order->paid_at->format('d.m.Y'))
            ->assertSee($item->starts_at->format('d.m.Y'))
            ->assertSee($item->expires_at->format('d.m.Y'))
            ->assertSee('10 дней')
            ->assertSee('Осталось дней: 5')
            ->assertSee('Скачивания')
            ->assertSee('<dd>2</dd>', false)
            ->assertSee(
                'href="'.route('account.purchases.download', $delivery).'"',
                false,
            );
    }

    public function test_account_hides_other_users_and_unpaid_purchases(): void
    {
        $owner = $this->createUser('owner@example.test');
        $other = $this->createUser('other@example.test');

        $this->createPurchase(
            $owner,
            [],
            [],
            ['product_name' => 'Собственная оплаченная покупка'],
        );
        $this->createPurchase(
            $other,
            [],
            [],
            ['product_name' => 'Чужая оплаченная покупка'],
        );
        $this->createPurchase(
            $owner,
            [
                'payment_status' => PaymentTransaction::STATUS_PENDING,
                'order_status' => Order::STATUS_PENDING,
                'paid_at' => null,
                'completed_at' => null,
            ],
            null,
            ['product_name' => 'Неоплаченная покупка'],
        );

        $this->actingAs($owner)
            ->get(route('account'))
            ->assertOk()
            ->assertSee('Собственная оплаченная покупка')
            ->assertDontSee('Чужая оплаченная покупка')
            ->assertDontSee('Неоплаченная покупка');
    }

    public function test_expired_purchase_has_completed_term_and_no_download_link(): void
    {
        $user = $this->createUser('expired@example.test');
        [, $item, $delivery] = $this->createPurchase(
            $user,
            [],
            [
                'starts_at' => now()->subDays(31),
                'expires_at' => now()->subDay(),
            ],
            [
                'product_name' => 'Просроченная покупка',
                'duration_days' => 30,
                'starts_at' => now()->subDays(31),
                'expires_at' => now()->subDay(),
            ],
        );

        $this->actingAs($user)
            ->get(route('account'))
            ->assertOk()
            ->assertSee($item->product_name)
            ->assertSee('Срок закончился')
            ->assertSee('Срок действия завершён')
            ->assertDontSee(
                'href="'.route('account.purchases.download', $delivery).'"',
                false,
            );
    }

    public function test_processing_cancelled_and_delivery_error_statuses_are_shown(): void
    {
        $user = $this->createUser('statuses@example.test');

        $this->createPurchase(
            $user,
            [
                'order_status' => Order::STATUS_PROCESSING,
                'completed_at' => null,
            ],
            null,
            ['product_name' => 'Покупка в обработке'],
        );
        $this->createPurchase(
            $user,
            [
                'order_status' => Order::STATUS_CANCELLED,
                'completed_at' => null,
            ],
            null,
            ['product_name' => 'Отменённая покупка'],
        );
        $this->createPurchase(
            $user,
            [
                'order_status' => Order::STATUS_PROCESSING,
                'completed_at' => null,
            ],
            [
                'status' => ProxyDelivery::STATUS_FAILED,
                'delivered_at' => null,
            ],
            ['product_name' => 'Покупка с ошибкой выдачи'],
        );

        $this->actingAs($user)
            ->get(route('account'))
            ->assertOk()
            ->assertSee('Покупка в обработке')
            ->assertSee('Обрабатывается')
            ->assertSee('Отменённая покупка')
            ->assertSee('Отменён')
            ->assertSee('Покупка с ошибкой выдачи')
            ->assertSee('Ошибка выдачи');
    }

    private function createUser(string $email): User
    {
        return User::query()->create([
            'email' => $email,
            'email_verified_at' => now(),
            'password' => Hash::make('password'),
            'is_auto_created' => false,
            'must_change_password' => false,
            'status' => User::STATUS_ACTIVE,
        ]);
    }

    /**
     * @param  array<string, mixed>  $orderOverrides
     * @param  array<string, mixed>|null  $deliveryOverrides
     * @param  array<string, mixed>  $itemOverrides
     * @return array{Order, OrderItem, ProxyDelivery|null}
     */
    private function createPurchase(
        User $user,
        array $orderOverrides = [],
        ?array $deliveryOverrides = [],
        array $itemOverrides = [],
    ): array {
        $sequence = ++$this->sequence;
        $category = Category::query()->create([
            'name' => "Категория {$sequence}",
            'slug' => "category-{$sequence}",
            'description' => null,
            'sort_order' => $sequence,
            'is_active' => true,
        ]);
        $product = Product::query()->create([
            'category_id' => $category->getKey(),
            'name' => "Товар {$sequence}",
            'slug' => "product-{$sequence}",
            'short_description' => null,
            'description' => null,
            'base_price' => '1000.00',
            'default_duration_days' => 30,
            'is_active' => true,
            'sort_order' => $sequence,
        ]);
        $order = Order::query()->create(array_merge([
            'order_number' => sprintf('NPK-20260724-%06d', $sequence),
            'user_id' => $user->getKey(),
            'customer_email' => $user->email,
            'subtotal' => '1000.00',
            'total' => '1000.00',
            'currency' => 'RUB',
            'payment_method' => 'test',
            'payment_status' => PaymentTransaction::STATUS_PAID,
            'order_status' => Order::STATUS_COMPLETED,
            'paid_at' => now()->subDay(),
            'completed_at' => now(),
        ], $orderOverrides));
        $item = OrderItem::query()->create(array_merge([
            'order_id' => $order->getKey(),
            'product_id' => $product->getKey(),
            'product_name' => $product->name,
            'quantity' => 1,
            'unit_price' => '1000.00',
            'total_price' => '1000.00',
            'duration_days' => 30,
            'starts_at' => now(),
            'expires_at' => now()->addDays(30),
        ], $itemOverrides));

        if ($deliveryOverrides === null) {
            return [$order, $item, null];
        }

        $filename = "proxy-NPK-20260724-{$sequence}.txt";
        $path = "{$user->getKey()}/{$order->order_number}/{$filename}";
        Storage::disk('private')->put($path, 'hello');

        $delivery = ProxyDelivery::query()->create(array_merge([
            'user_id' => $user->getKey(),
            'order_id' => $order->getKey(),
            'order_item_id' => $item->getKey(),
            'file_path' => $path,
            'original_filename' => $filename,
            'status' => ProxyDelivery::STATUS_ACTIVE,
            'starts_at' => $item->starts_at,
            'expires_at' => $item->expires_at,
            'delivered_at' => now(),
            'download_count' => 0,
            'last_downloaded_at' => null,
        ], $deliveryOverrides));

        return [$order, $item, $delivery];
    }
}
