<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\PaymentTransaction;
use App\Models\Product;
use App\Models\ProductDurationOption;
use App\Models\ProxyDelivery;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminPanelTest extends TestCase
{
    use RefreshDatabase;

    private int $orderSequence = 0;

    public function test_guest_is_redirected_from_admin_panel_to_login(): void
    {
        $this->get(route('admin.dashboard'))
            ->assertRedirect(route('login'));
    }

    public function test_regular_user_gets_forbidden_response_from_admin_panel(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('admin.dashboard'))
            ->assertForbidden();
    }

    public function test_admin_can_open_dashboard(): void
    {
        $this->actingAs($this->admin())
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Панель управления')
            ->assertSee('Категории')
            ->assertSee('Товары')
            ->assertSee('Заказы')
            ->assertSee('Пользователи');
    }

    public function test_dashboard_shows_current_metrics(): void
    {
        $admin = $this->admin();
        User::factory()->count(2)->create();
        Product::factory()->count(2)->create(['is_active' => true]);
        Product::factory()->create(['is_active' => false]);
        $this->createOrder($admin, [
            'payment_status' => PaymentTransaction::STATUS_PAID,
            'order_status' => Order::STATUS_PROCESSING,
        ]);
        $this->createOrder($admin, [
            'payment_status' => PaymentTransaction::STATUS_PAID,
            'order_status' => Order::STATUS_COMPLETED,
        ]);
        $this->createOrder($admin, [
            'payment_status' => PaymentTransaction::STATUS_FAILED,
            'order_status' => Order::STATUS_FAILED,
        ]);
        $this->createOrder($admin, [
            'payment_status' => PaymentTransaction::STATUS_CANCELLED,
            'order_status' => Order::STATUS_CANCELLED,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSeeInOrder([
                'Пользователи',
                '3',
                'Активные товары',
                '2',
                'Все заказы',
                '4',
                'Оплачено',
                '2',
                'В обработке',
                '1',
                'Завершено',
                '1',
                'Ошибка или отмена',
                '2',
            ]);
    }

    public function test_admin_can_create_category_with_automatic_slug(): void
    {
        $this->actingAs($this->admin())
            ->post(route('admin.categories.store'), [
                'name' => 'Прокси для бизнеса',
                'slug' => '',
                'description' => 'Описание категории',
                'sort_order' => 10,
                'is_active' => '1',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('categories', [
            'name' => 'Прокси для бизнеса',
            'slug' => 'proksi-dlia-biznesa',
            'sort_order' => 10,
            'is_active' => true,
        ]);
    }

    public function test_category_slug_must_be_unique(): void
    {
        Category::factory()->create(['slug' => 'business']);

        $this->actingAs($this->admin())
            ->from(route('admin.categories.create'))
            ->post(route('admin.categories.store'), [
                'name' => 'Другая категория',
                'slug' => 'business',
                'description' => null,
                'sort_order' => 0,
                'is_active' => '1',
            ])
            ->assertRedirect(route('admin.categories.create'))
            ->assertSessionHasErrors('slug');
    }

    public function test_admin_can_update_category(): void
    {
        $category = Category::factory()->create(['slug' => 'old-category']);

        $this->actingAs($this->admin())
            ->put(route('admin.categories.update', $category), [
                'name' => 'Обновлённая категория',
                'slug' => 'updated-category',
                'description' => 'Новое описание',
                'sort_order' => 30,
            ])
            ->assertRedirect(route('admin.categories.edit', 'updated-category'));

        $this->assertDatabaseHas('categories', [
            'id' => $category->getKey(),
            'slug' => 'updated-category',
            'sort_order' => 30,
            'is_active' => false,
        ]);
    }

    public function test_category_with_products_cannot_be_deleted(): void
    {
        $category = Category::factory()->create();
        Product::factory()->for($category)->create();

        $this->actingAs($this->admin())
            ->delete(route('admin.categories.destroy', $category))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('categories', ['id' => $category->getKey()]);
    }

    public function test_unused_category_can_be_deleted(): void
    {
        $category = Category::factory()->create();

        $this->actingAs($this->admin())
            ->delete(route('admin.categories.destroy', $category))
            ->assertRedirect(route('admin.categories.index'));

        $this->assertDatabaseMissing('categories', ['id' => $category->getKey()]);
    }

    public function test_admin_can_create_product(): void
    {
        $category = Category::factory()->create();

        $response = $this->actingAs($this->admin())
            ->post(route('admin.products.store'), $this->productData($category, [
                'name' => 'Новый прокси',
                'slug' => '',
            ]));

        $product = Product::query()->sole();
        $response->assertRedirect(route('admin.products.edit', $product));
        $this->assertSame('novyi-proksi', $product->slug);
        $this->assertSame('1250.50', $product->base_price);
    }

    public function test_admin_can_update_product(): void
    {
        $category = Category::factory()->create();
        $otherCategory = Category::factory()->create();
        $product = Product::factory()->for($category)->create(['slug' => 'old-product']);

        $this->actingAs($this->admin())
            ->put(
                route('admin.products.update', $product),
                $this->productData($otherCategory, [
                    'name' => 'Обновлённый товар',
                    'slug' => 'updated-product',
                    'base_price' => '999,90',
                    'sort_order' => 25,
                ]),
            )
            ->assertRedirect(route('admin.products.edit', 'updated-product'));

        $this->assertDatabaseHas('products', [
            'id' => $product->getKey(),
            'category_id' => $otherCategory->getKey(),
            'slug' => 'updated-product',
            'base_price' => '999.90',
            'sort_order' => 25,
        ]);
    }

    public function test_product_price_and_default_duration_are_validated(): void
    {
        $category = Category::factory()->create();

        $this->actingAs($this->admin())
            ->post(route('admin.products.store'), $this->productData($category, [
                'base_price' => '-1',
                'default_duration_days' => 0,
            ]))
            ->assertSessionHasErrors(['base_price', 'default_duration_days']);

        $this->assertDatabaseCount('products', 0);
    }

    public function test_admin_can_add_duration_option(): void
    {
        $product = Product::factory()->create();

        $this->actingAs($this->admin())
            ->post(route('admin.products.duration-options.store', $product), [
                'title' => '90 дней',
                'duration_days' => 90,
                'price' => '2500,50',
                'sort_order' => 20,
                'is_active' => '1',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('product_duration_options', [
            'product_id' => $product->getKey(),
            'title' => '90 дней',
            'duration_days' => 90,
            'price' => '2500.50',
            'is_active' => true,
        ]);
    }

    public function test_duration_option_price_and_days_are_validated(): void
    {
        $product = Product::factory()->create();

        $this->actingAs($this->admin())
            ->post(route('admin.products.duration-options.store', $product), [
                'title' => 'Некорректный срок',
                'duration_days' => 0,
                'price' => 'не число',
                'sort_order' => 0,
                'is_active' => '1',
            ])
            ->assertSessionHasErrors(['duration_days', 'price']);

        $this->assertDatabaseCount('product_duration_options', 0);
    }

    public function test_duration_option_cannot_be_changed_through_another_product(): void
    {
        $product = Product::factory()->create();
        $otherProduct = Product::factory()->create();
        $option = ProductDurationOption::factory()->for($product)->create();

        $this->actingAs($this->admin())
            ->put(route('admin.products.duration-options.update', [$otherProduct, $option]), [
                'title' => 'Подмена',
                'duration_days' => 365,
                'price' => '100.00',
                'sort_order' => 0,
                'is_active' => '1',
            ])
            ->assertNotFound();

        $this->assertNotSame('Подмена', $option->fresh()->title);
    }

    public function test_admin_sees_orders_list(): void
    {
        $admin = $this->admin();
        $order = $this->createOrder($admin);

        $this->actingAs($admin)
            ->get(route('admin.orders.index'))
            ->assertOk()
            ->assertSee($order->order_number)
            ->assertSee($order->customer_email)
            ->assertSee('1 000 RUB');
    }

    public function test_order_filters_work(): void
    {
        $admin = $this->admin();
        $matching = $this->createOrder($admin, [
            'customer_email' => 'target@example.test',
            'payment_status' => PaymentTransaction::STATUS_PAID,
            'order_status' => Order::STATUS_COMPLETED,
        ]);
        $other = $this->createOrder($admin, [
            'customer_email' => 'other@example.test',
            'payment_status' => PaymentTransaction::STATUS_FAILED,
            'order_status' => Order::STATUS_FAILED,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.orders.index', [
                'email' => 'target@',
                'payment_status' => PaymentTransaction::STATUS_PAID,
                'order_status' => Order::STATUS_COMPLETED,
            ]))
            ->assertOk()
            ->assertSee($matching->order_number)
            ->assertDontSee($other->order_number);
    }

    public function test_admin_sees_order_details_without_sensitive_payloads_or_paths(): void
    {
        $admin = $this->admin();
        $order = $this->createOrder($admin);
        $item = $this->createOrderItem($order, ['product_name' => 'Снимок товара']);
        PaymentTransaction::query()->create([
            'order_id' => $order->getKey(),
            'provider' => 'test',
            'external_payment_id' => 'secret-reference',
            'amount' => '1000.00',
            'currency' => 'RUB',
            'status' => PaymentTransaction::STATUS_PAID,
            'request_payload' => ['secret' => 'private-request'],
            'response_payload' => ['token' => 'private-response'],
            'paid_at' => now(),
        ]);
        ProxyDelivery::query()->create([
            'user_id' => $admin->getKey(),
            'order_id' => $order->getKey(),
            'order_item_id' => $item->getKey(),
            'file_path' => $admin->getKey().'/'.$order->order_number.'/proxy.txt',
            'original_filename' => 'proxy.txt',
            'status' => ProxyDelivery::STATUS_ACTIVE,
            'starts_at' => now(),
            'expires_at' => now()->addDays(30),
            'delivered_at' => now(),
        ]);

        $this->actingAs($admin)
            ->get(route('admin.orders.show', $order))
            ->assertOk()
            ->assertSee('Снимок товара')
            ->assertSee('30 дней')
            ->assertSee('proxy.txt')
            ->assertDontSee('secret-reference')
            ->assertDontSee('private-request')
            ->assertDontSee('private-response')
            ->assertDontSee($admin->getKey().'/'.$order->order_number.'/proxy.txt');
    }

    public function test_admin_sees_and_filters_users_and_opens_user_details(): void
    {
        $admin = $this->admin();
        $target = User::factory()->create(['email' => 'customer@example.test']);
        $other = User::factory()->create(['email' => 'other@example.test']);
        $order = $this->createOrder($target);
        $this->createOrderItem($order, ['product_name' => 'Покупка пользователя']);

        $this->actingAs($admin)
            ->get(route('admin.users.index', ['email' => 'customer@']))
            ->assertOk()
            ->assertSee($target->email)
            ->assertDontSee($other->email);

        $this->actingAs($admin)
            ->get(route('admin.users.show', $target))
            ->assertOk()
            ->assertSee($target->email)
            ->assertSee($order->order_number)
            ->assertSee('Покупка пользователя')
            ->assertDontSee($target->password);
    }

    public function test_regular_user_cannot_open_any_admin_route(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();
        $product = Product::factory()->for($category)->create();
        $option = ProductDurationOption::factory()->for($product)->create();
        $order = $this->createOrder($user);

        $routes = [
            ['GET', route('admin.dashboard')],
            ['GET', route('admin.categories.index')],
            ['GET', route('admin.categories.create')],
            ['POST', route('admin.categories.store')],
            ['GET', route('admin.categories.edit', $category)],
            ['PUT', route('admin.categories.update', $category)],
            ['PATCH', route('admin.categories.toggle', $category)],
            ['DELETE', route('admin.categories.destroy', $category)],
            ['GET', route('admin.products.index')],
            ['GET', route('admin.products.create')],
            ['POST', route('admin.products.store')],
            ['GET', route('admin.products.edit', $product)],
            ['PUT', route('admin.products.update', $product)],
            ['PATCH', route('admin.products.toggle', $product)],
            ['POST', route('admin.products.duration-options.store', $product)],
            ['PUT', route('admin.products.duration-options.update', [$product, $option])],
            ['DELETE', route('admin.products.duration-options.destroy', [$product, $option])],
            ['GET', route('admin.orders.index')],
            ['GET', route('admin.orders.show', $order)],
            ['GET', route('admin.users.index')],
            ['GET', route('admin.users.show', $user)],
        ];

        $this->actingAs($user);

        foreach ($routes as [$method, $url]) {
            $this->call($method, $url)->assertForbidden();
        }
    }

    private function admin(): User
    {
        return User::factory()->admin()->create();
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function productData(Category $category, array $overrides = []): array
    {
        return array_merge([
            'category_id' => $category->getKey(),
            'name' => 'Тестовый товар',
            'slug' => 'test-product',
            'short_description' => 'Краткое описание',
            'description' => 'Полное описание',
            'base_price' => '1250.50',
            'default_duration_days' => 30,
            'sort_order' => 10,
            'is_active' => '1',
        ], $overrides);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function createOrder(User $user, array $overrides = []): Order
    {
        $sequence = ++$this->orderSequence;

        return Order::query()->create(array_merge([
            'order_number' => sprintf('NPK-20260728-%06d', $sequence),
            'user_id' => $user->getKey(),
            'customer_email' => $user->email,
            'subtotal' => '1000.00',
            'total' => '1000.00',
            'currency' => 'RUB',
            'payment_method' => 'test',
            'payment_status' => PaymentTransaction::STATUS_PENDING,
            'order_status' => Order::STATUS_PENDING,
            'paid_at' => null,
            'completed_at' => null,
        ], $overrides));
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function createOrderItem(Order $order, array $overrides = []): OrderItem
    {
        $product = Product::factory()->create();

        return OrderItem::query()->create(array_merge([
            'order_id' => $order->getKey(),
            'product_id' => $product->getKey(),
            'product_name' => $product->name,
            'quantity' => 1,
            'unit_price' => '1000.00',
            'total_price' => '1000.00',
            'duration_days' => 30,
            'starts_at' => now(),
            'expires_at' => now()->addDays(30),
        ], $overrides));
    }
}
