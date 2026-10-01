<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use App\Models\Supplier;
use Illuminate\Database\Seeder;

class InventorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = collect(['Electronics', 'Office Supplies', 'Furniture', 'Accessories'])
            ->map(fn (string $name) => Category::firstOrCreate(
                ['slug' => str($name)->slug()],
                ['name' => $name, 'description' => $name.' inventory'],
            ));
        $suppliers = Supplier::factory(5)->create();

        Product::factory(30)->make()->each(function (Product $product) use ($categories, $suppliers): void {
            $product->category()->associate($categories->random());
            $product->save();
            $product->suppliers()->sync($suppliers->random(2)->modelKeys());
        });
    }
}
