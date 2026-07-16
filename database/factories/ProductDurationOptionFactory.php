<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\ProductDurationOption;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProductDurationOption>
 */
class ProductDurationOptionFactory extends Factory
{
    public function definition(): array
    {
        $durationDays = fake()->numberBetween(1, 365);

        return [
            'product_id' => Product::factory(),
            'title' => $durationDays.' дней',
            'duration_days' => $durationDays,
            'price' => fake()->numberBetween(100, 10000).'.00',
            'is_active' => true,
            'sort_order' => fake()->numberBetween(0, 100),
        ];
    }
}
