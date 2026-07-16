<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductDurationOption;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CatalogTest extends TestCase
{
    use RefreshDatabase;

    public function test_catalog_opens_and_shows_empty_state(): void
    {
        $this->get('/catalog')
            ->assertOk()
            ->assertSee('Категории пока не добавлены');
    }

    public function test_catalog_shows_only_active_categories_and_products(): void
    {
        $category = Category::factory()->create([
            'name' => 'Активная категория',
            'slug' => 'active-category',
        ]);
        $hiddenCategory = Category::factory()->create([
            'name' => 'Скрытая категория',
            'slug' => 'hidden-category',
            'is_active' => false,
        ]);

        Product::factory()->for($category)->create([
            'name' => 'Активный товар',
            'slug' => 'active-product',
        ]);
        Product::factory()->for($category)->create([
            'name' => 'Скрытый товар',
            'slug' => 'hidden-product',
            'is_active' => false,
        ]);
        Product::factory()->for($hiddenCategory)->create([
            'name' => 'Товар скрытой категории',
            'slug' => 'hidden-category-product',
        ]);

        $this->get('/catalog')
            ->assertOk()
            ->assertSee('Активная категория')
            ->assertSee('Активный товар')
            ->assertDontSee('Скрытая категория')
            ->assertDontSee('Скрытый товар')
            ->assertDontSee('Товар скрытой категории');
    }

    public function test_catalog_uses_two_select_queries_regardless_of_category_count(): void
    {
        Category::factory()
            ->count(4)
            ->create()
            ->each(fn (Category $category) => Product::factory()->for($category)->count(3)->create());

        DB::flushQueryLog();
        DB::enableQueryLog();

        $this->get('/catalog')->assertOk();

        $selectQueries = collect(DB::getQueryLog())
            ->pluck('query')
            ->filter(fn (string $query): bool => str_starts_with(strtolower(ltrim($query)), 'select'));

        DB::disableQueryLog();

        $this->assertCount(2, $selectQueries);
    }

    public function test_category_page_shows_active_products_in_sort_order(): void
    {
        $category = Category::factory()->create([
            'name' => 'Прокси по странам',
            'slug' => 'countries',
            'description' => 'Описание категории стран.',
        ]);

        Product::factory()->for($category)->create([
            'name' => 'Второй товар',
            'slug' => 'second-product',
            'sort_order' => 20,
        ]);
        Product::factory()->for($category)->create([
            'name' => 'Первый товар',
            'slug' => 'first-product',
            'sort_order' => 10,
        ]);
        Product::factory()->for($category)->create([
            'name' => 'Неактивный товар категории',
            'slug' => 'inactive-category-product',
            'is_active' => false,
        ]);

        $this->get('/catalog/countries')
            ->assertOk()
            ->assertSee('Прокси по странам')
            ->assertSee('Описание категории стран.')
            ->assertSeeInOrder(['Первый товар', 'Второй товар'])
            ->assertDontSee('Неактивный товар категории');
    }

    public function test_missing_and_inactive_category_slugs_return_not_found(): void
    {
        Category::factory()->create([
            'slug' => 'disabled-category',
            'is_active' => false,
        ]);

        $this->get('/catalog/missing-category')->assertNotFound();
        $this->get('/catalog/disabled-category')->assertNotFound();
    }

    public function test_product_page_shows_category_and_active_duration_options(): void
    {
        $category = Category::factory()->create([
            'name' => 'Мобильные прокси',
            'slug' => 'mobile-proxies',
        ]);
        $product = Product::factory()->for($category)->create([
            'name' => 'Мобильный прокси России',
            'slug' => 'mobile-proxy-russia',
            'base_price' => '1000.00',
        ]);

        ProductDurationOption::factory()->for($product)->create([
            'title' => '30 дней',
            'duration_days' => 30,
            'price' => '1000.00',
            'sort_order' => 10,
        ]);
        ProductDurationOption::factory()->for($product)->create([
            'title' => '90 дней',
            'duration_days' => 90,
            'price' => '2500.50',
            'sort_order' => 20,
        ]);
        ProductDurationOption::factory()->for($product)->create([
            'title' => 'Скрытый срок',
            'duration_days' => 180,
            'price' => '4000.00',
            'is_active' => false,
        ]);

        $this->get('/products/mobile-proxy-russia')
            ->assertOk()
            ->assertSee('Мобильный прокси России')
            ->assertSee('Мобильные прокси')
            ->assertSeeInOrder(['30 дней', '90 дней'])
            ->assertSee('1 000 ₽')
            ->assertSee('2 500,50 ₽')
            ->assertDontSee('Скрытый срок')
            ->assertSee('Купить');
    }

    public function test_inactive_product_and_product_of_inactive_category_return_not_found(): void
    {
        $category = Category::factory()->create();
        $inactiveProduct = Product::factory()->for($category)->create([
            'slug' => 'inactive-product',
            'is_active' => false,
        ]);
        $inactiveCategory = Category::factory()->create(['is_active' => false]);
        $productInInactiveCategory = Product::factory()->for($inactiveCategory)->create([
            'slug' => 'product-in-inactive-category',
        ]);

        $this->get('/products/'.$inactiveProduct->slug)->assertNotFound();
        $this->get('/products/'.$productInInactiveCategory->slug)->assertNotFound();
        $this->get('/products/missing-product')->assertNotFound();
    }

    public function test_product_without_duration_options_has_clear_empty_state(): void
    {
        $product = Product::factory()->create([
            'slug' => 'product-without-options',
        ]);

        $this->get('/products/'.$product->slug)
            ->assertOk()
            ->assertSee('Варианты срока пока не добавлены');
    }
}
