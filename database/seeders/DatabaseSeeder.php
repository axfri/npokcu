<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        DB::transaction(function (): void {
            $countryCategory = Category::create([
                'name' => 'Демо: по странам',
                'slug' => 'demo-countries',
                'description' => 'Демонстрационная категория, не каталог действующего сайта.',
                'sort_order' => 10,
            ]);

            $purposeCategory = Category::create([
                'name' => 'Демо: по назначению',
                'slug' => 'demo-purposes',
                'description' => 'Демонстрационная категория, не каталог действующего сайта.',
                'sort_order' => 20,
            ]);

            $products = collect([
                [$countryCategory, 'Демо-прокси страны A', 'demo-country-a', '100.00'],
                [$countryCategory, 'Демо-прокси страны B', 'demo-country-b', '150.00'],
                [$purposeCategory, 'Демо-прокси для сервиса A', 'demo-service-a', '80.00'],
                [$purposeCategory, 'Демо-прокси для сервиса B', 'demo-service-b', '120.00'],
            ])->map(function (array $data, int $index): Product {
                [$category, $name, $slug, $price] = $data;

                $product = Product::create([
                    'category_id' => $category->id,
                    'name' => $name,
                    'slug' => $slug,
                    'short_description' => 'Только демонстрационные данные.',
                    'base_price' => $price,
                    'default_duration_days' => 30,
                    'sort_order' => ($index + 1) * 10,
                ]);

                $product->durationOptions()->createMany([
                    [
                        'title' => '30 дней',
                        'duration_days' => 30,
                        'price' => $price,
                        'sort_order' => 10,
                    ],
                    [
                        'title' => '90 дней',
                        'duration_days' => 90,
                        'price' => number_format((float) $price * 3, 2, '.', ''),
                        'sort_order' => 20,
                    ],
                ]);

                return $product;
            });

            $user = User::create([
                'email' => 'demo@example.com',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
                'status' => User::STATUS_ACTIVE,
            ]);

            $order = Order::create([
                'order_number' => 'DEMO-000001',
                'user_id' => $user->id,
                'customer_email' => $user->email,
                'subtotal' => '250.00',
                'total' => '250.00',
                'currency' => 'RUB',
                'payment_method' => 'test',
                'payment_status' => Order::STATUS_PAID,
                'order_status' => Order::STATUS_COMPLETED,
                'paid_at' => now(),
                'completed_at' => now(),
            ]);

            foreach ($products->take(2) as $product) {
                $order->items()->create([
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'quantity' => 1,
                    'unit_price' => $product->base_price,
                    'total_price' => $product->base_price,
                    'duration_days' => 30,
                    'starts_at' => now(),
                    'expires_at' => now()->addDays(30),
                ]);
            }
        });
    }
}
