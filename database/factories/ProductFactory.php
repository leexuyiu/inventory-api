<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Product> */
class ProductFactory extends Factory
{
    public function definition(): array
    {
        return [
            'category_id' => Category::factory(),
            'sku' => strtoupper(fake()->unique()->bothify('SKU-????-####')),
            'name' => fake()->words(3, true),
            'description' => fake()->sentence(),
            'price' => fake()->randomFloat(2, 1, 1000),
            'stock_quantity' => fake()->numberBetween(0, 200),
            'reorder_level' => 10,
            'is_active' => true,
        ];
    }
}
