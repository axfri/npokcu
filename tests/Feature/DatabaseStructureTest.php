<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class DatabaseStructureTest extends TestCase
{
    use RefreshDatabase;

    public function test_required_tables_and_columns_exist(): void
    {
        $this->assertTrue(Schema::hasColumns('users', [
            'email', 'is_auto_created', 'must_change_password', 'status',
        ]));
        $this->assertTrue(Schema::hasColumns('products', [
            'category_id', 'base_price', 'default_duration_days',
        ]));
        $this->assertTrue(Schema::hasColumns('orders', [
            'order_number', 'customer_email', 'payment_status', 'order_status',
        ]));
        $this->assertTrue(Schema::hasColumns('payment_transactions', [
            'request_payload', 'response_payload', 'paid_at',
        ]));
        $this->assertTrue(Schema::hasTable('proxy_deliveries'));
        $this->assertTrue(Schema::hasTable('download_logs'));
    }

    public function test_demo_seeder_creates_expected_data_and_relations(): void
    {
        $this->seed();

        $this->assertSame(2, Category::count());
        $this->assertSame(4, Product::count());

        $user = User::where('email', 'demo@example.com')->firstOrFail();
        $order = Order::where('order_number', 'DEMO-000001')->firstOrFail();

        $this->assertTrue($user->orders->contains($order));
        $this->assertTrue($order->user->is($user));
        $this->assertCount(2, $order->items);
        $this->assertNotNull($order->items->first()->product);
        $this->assertCount(2, Product::firstOrFail()->durationOptions);
    }

    public function test_model_casts_keep_money_and_flags_safe(): void
    {
        $this->seed();

        $product = Product::firstOrFail();
        $user = User::firstOrFail();

        $this->assertSame('100.00', $product->base_price);
        $this->assertIsBool($product->is_active);
        $this->assertIsBool($user->is_auto_created);
    }
}
