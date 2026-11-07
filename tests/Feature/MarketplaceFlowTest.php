<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MarketplaceFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_storefront_is_public_but_workspaces_are_guarded(): void
    {
        $this->get('/en')->assertOk();
        $this->get('/en/products')->assertOk();
        $this->get('/dashboard')->assertRedirect('/login');
        $this->get('/vendor')->assertRedirect('/login');
    }

    public function test_users_register_as_customers_and_cannot_self_assign_admin(): void
    {
        $this->post('/register', [
            'name' => 'New customer',
            'email' => 'customer@example.test',
            'password' => 'SecurePass123',
            'password_confirmation' => 'SecurePass123',
            'role' => 'admin',
        ])->assertRedirect('/account');

        $this->assertAuthenticated();
        $this->assertDatabaseHas('users', ['email' => 'customer@example.test', 'role' => 'customer']);
        $this->get('/dashboard')->assertForbidden();
    }

    public function test_admin_approval_vendor_product_and_physical_checkout_work(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $seller = User::factory()->create(['role' => 'customer']);
        $buyer = User::factory()->create(['role' => 'customer']);
        $category = Category::create(['name' => 'Electronics', 'slug' => 'electronics', 'is_active' => true]);

        $this->actingAs($seller)->post('/account/vendor-application', [
            'store_name' => 'Mora Electronics',
            'description' => 'Devices and accessories.',
        ])->assertRedirect();

        $vendor = Vendor::firstOrFail();
        $this->assertSame('pending', $vendor->status);

        $this->actingAs($admin)->patch('/dashboard/vendors/'.$vendor->id, [
            'status' => 'approved',
        ])->assertRedirect();

        $seller->refresh();
        $this->assertSame('vendor', $seller->role);

        $this->actingAs($seller)->post('/vendor/products', [
            'title' => 'Wireless Keyboard',
            'category_id' => $category->id,
            'type' => 'physical',
            'price' => '29.99',
            'stock' => 10,
            'status' => 'published',
            'description' => 'Compact wireless keyboard.',
        ])->assertRedirect();

        $product = Product::firstOrFail();
        $this->get('/en/product/'.$product->slug)->assertOk();
        $this->actingAs($buyer)->post('/cart/'.$product->id, ['quantity' => 2])->assertRedirect('/cart');
        $this->get('/checkout')->assertOk();
        $this->post('/checkout', [
            'customer_name' => 'Buyer Name',
            'customer_email' => 'buyer@example.test',
            'shipping_address' => 'Street 12, Phnom Penh, Cambodia',
        ])->assertRedirect('/account/orders');

        $this->assertDatabaseHas('orders', [
            'user_id' => $buyer->id,
            'vendor_id' => $vendor->id,
            'status' => 'pending',
            'payment_method' => 'cod',
            'total' => 59.98,
        ]);
        $this->assertDatabaseHas('order_items', [
            'product_id' => $product->id,
            'quantity' => 2,
            'line_total' => 59.98,
        ]);
        $this->assertSame(8, $product->fresh()->stock);
        $this->assertSame(1, Order::count());
    }

    public function test_vendor_cannot_edit_another_vendors_product(): void
    {
        $seller = User::factory()->create(['role' => 'vendor']);
        $other = User::factory()->create(['role' => 'vendor']);
        $ownVendor = Vendor::create(['user_id' => $seller->id, 'name' => 'First Store', 'slug' => 'first-store', 'status' => 'approved']);
        $otherVendor = Vendor::create(['user_id' => $other->id, 'name' => 'Second Store', 'slug' => 'second-store', 'status' => 'approved']);
        $category = Category::create(['name' => 'Fashion', 'slug' => 'fashion', 'is_active' => true]);
        $product = Product::create([
            'vendor_id' => $otherVendor->id, 'category_id' => $category->id,
            'title' => 'Linen shirt', 'slug' => 'linen-shirt', 'type' => 'physical',
            'price' => 12.50, 'stock' => 5, 'status' => 'published',
        ]);
        $this->actingAs($seller)->delete('/vendor/products/'.$product->id)->assertNotFound();
        $this->assertDatabaseHas('products', ['id' => $product->id]);
    }

    public function test_digital_listing_cannot_be_added_to_checkout(): void
    {
        $seller = User::factory()->create(['role' => 'vendor']);
        $vendor = Vendor::create(['user_id' => $seller->id, 'name' => 'Digital Studio', 'slug' => 'digital-studio', 'status' => 'approved']);
        $category = Category::create(['name' => 'Digital Products', 'slug' => 'digital-products', 'is_active' => true]);
        $product = Product::create([
            'vendor_id' => $vendor->id, 'category_id' => $category->id,
            'title' => 'Photo bundle', 'slug' => 'photo-bundle', 'type' => 'digital',
            'price' => 5, 'stock' => 5, 'status' => 'published',
        ]);

        $this->post('/cart/'.$product->id, ['quantity' => 1])
            ->assertSessionHasErrors('quantity');
        $this->assertEmpty(session('cart', []));
    }
}
