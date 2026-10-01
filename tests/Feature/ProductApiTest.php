<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_product_routes_require_sanctum_authentication(): void
    {
        $this->getJson('/api/products')->assertUnauthorized();
    }

    public function test_authenticated_user_can_create_product_with_suppliers(): void
    {
        $this->actingAs(User::factory()->create(), 'sanctum');
        $category = Category::factory()->create();
        $supplier = Supplier::factory()->create();

        $this->postJson('/api/products', [
            'category_id' => $category->id,
            'sku' => '  widget-001 ',
            'name' => 'Widget',
            'price' => 12.5,
            'stock_quantity' => 4,
            'reorder_level' => 5,
            'supplier_ids' => [$supplier->id],
        ])
            ->assertCreated()
            ->assertJsonPath('data.sku', 'WIDGET-001')
            ->assertJsonPath('data.stock_status', 'low_stock')
            ->assertJsonPath('data.suppliers.0.id', $supplier->id);

        $this->assertDatabaseHas('products', ['sku' => 'WIDGET-001']);
    }

    public function test_index_filters_by_category_price_and_stock_level_and_paginates(): void
    {
        $this->actingAs(User::factory()->create(), 'sanctum');
        $category = Category::factory()->create();
        Product::factory()->create([
            'category_id' => $category->id,
            'price' => 20,
            'stock_quantity' => 3,
            'reorder_level' => 5,
        ]);
        Product::factory()->create([
            'category_id' => $category->id,
            'price' => 200,
            'stock_quantity' => 3,
            'reorder_level' => 5,
        ]);
        Product::factory()->create([
            'price' => 20,
            'stock_quantity' => 3,
            'reorder_level' => 5,
        ]);

        $this->getJson('/api/products?category_id='.$category->id.'&min_price=10&max_price=30&stock_level=low&per_page=1')
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('meta.per_page', 1)
            ->assertJsonPath('data.0.stock_status', 'low_stock');
    }

    public function test_product_index_cache_is_invalidated_after_creation(): void
    {
        $this->actingAs(User::factory()->create(), 'sanctum');
        $category = Category::factory()->create();
        Product::factory()->create(['category_id' => $category->id]);

        $this->getJson('/api/products')->assertOk()->assertJsonPath('meta.total', 1);

        $this->postJson('/api/products', [
            'category_id' => $category->id,
            'sku' => 'CACHE-INVALIDATION-001',
            'name' => 'New product',
            'price' => 10,
            'stock_quantity' => 10,
        ])->assertCreated();

        $this->getJson('/api/products')->assertOk()->assertJsonPath('meta.total', 2);
    }

    public function test_api_routes_are_rate_limited(): void
    {
        $this->actingAs(User::factory()->create(), 'sanctum');

        for ($request = 0; $request < 60; $request++) {
            $this->getJson('/api/products')->assertOk();
        }

        $this->getJson('/api/products')->assertTooManyRequests();
    }

    public function test_authenticated_user_can_view_and_update_product(): void
    {
        $this->actingAs(User::factory()->create(), 'sanctum');
        $product = Product::factory()->create(['name' => 'Old name']);

        $this->getJson('/api/products/'.$product->id)
            ->assertOk()
            ->assertJsonPath('data.name', 'Old name');

        $this->patchJson('/api/products/'.$product->id, ['name' => 'New name'])
            ->assertOk()
            ->assertJsonPath('data.name', 'New name');
    }

    public function test_delete_soft_deletes_product_and_removes_it_from_index(): void
    {
        $this->actingAs(User::factory()->create(), 'sanctum');
        $product = Product::factory()->create();

        $this->deleteJson('/api/products/'.$product->id)->assertNoContent();

        $this->assertSoftDeleted('products', ['id' => $product->id]);
        $this->getJson('/api/products')->assertOk()->assertJsonCount(0, 'data');
        $this->getJson('/api/products/'.$product->id)->assertNotFound();
    }

    public function test_authenticated_user_can_restore_a_soft_deleted_product(): void
    {
        $this->actingAs(User::factory()->create(), 'sanctum');
        $product = Product::factory()->create();
        $product->delete();

        $this->postJson('/api/products/'.$product->id.'/restore')
            ->assertOk()
            ->assertJsonPath('data.id', $product->id);

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'deleted_at' => null,
        ]);
        $this->getJson('/api/products')->assertOk()->assertJsonCount(1, 'data');
    }

    public function test_registration_login_and_logout_issue_and_revoke_tokens(): void
    {
        $this->postJson('/api/auth/register', [
            'name' => 'API User',
            'email' => 'api@example.com',
            'password' => 'secret-password',
            'password_confirmation' => 'secret-password',
        ])->assertCreated()->assertJsonPath('data.user.email', 'api@example.com');

        $login = $this->postJson('/api/auth/login', [
            'email' => 'api@example.com',
            'password' => 'secret-password',
        ])->assertOk();

        $token = $login->json('data.token');

        $this->withToken($token)->postJson('/api/auth/logout')->assertOk();
        $this->assertDatabaseMissing('personal_access_tokens', [
            'token' => hash('sha256', $token),
        ]);
    }

    public function test_product_validation_returns_field_errors(): void
    {
        $this->actingAs(User::factory()->create(), 'sanctum');

        $this->postJson('/api/products', ['name' => 'Incomplete'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['category_id', 'sku', 'price', 'stock_quantity']);
    }
}
