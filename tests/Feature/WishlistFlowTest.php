<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WishlistFlowTest extends TestCase
{
    use RefreshDatabase;

    private function listing(string $title = 'Handcrafted notebook'): array
    {
        $seller = User::factory()->create(['role' => 'vendor']);
        $vendor = Vendor::create([
            'user_id' => $seller->id,
            'name' => 'Paper Studio',
            'slug' => 'paper-studio',
            'status' => 'approved',
        ]);
        $category = Category::create([
            'name' => 'Stationery',
            'slug' => 'stationery',
            'is_active' => true,
        ]);
        $product = Product::create([
            'vendor_id' => $vendor->id,
            'category_id' => $category->id,
            'title' => $title,
            'slug' => 'handcrafted-notebook',
            'type' => 'physical',
            'status' => 'published',
            'price' => 11.50,
            'stock' => 8,
        ]);

        return [$product, $vendor, $category];
    }

    public function test_wishlist_requires_login_and_is_private_and_idempotent(): void
    {
        [$product] = $this->listing();
        $owner = User::factory()->create(['role' => 'customer']);
        $other = User::factory()->create(['role' => 'customer']);

        $this->get('/account/wishlist')->assertRedirect('/login');
        $this->post('/account/wishlist/'.$product->id)->assertRedirect('/login');
        $this->delete('/account/wishlist/'.$product->id)->assertRedirect('/login');
        $this->get('/en/product/'.$product->slug)->assertOk()->assertSee('Sign in to save product');

        $this->actingAs($owner)->get('/en/product/'.$product->slug)
            ->assertOk()->assertSee('Save to Wishlist');
        $this->post('/account/wishlist/'.$product->id)->assertRedirect();
        $this->post('/account/wishlist/'.$product->id)->assertRedirect();
        $this->assertDatabaseCount('wishlist_items', 1);
        $this->assertDatabaseHas('wishlist_items', ['user_id' => $owner->id, 'product_id' => $product->id]);
        $this->get('/account/wishlist')->assertOk()->assertSee($product->title)
            ->assertSee('Remove from Wishlist');
        $this->get('/en/product/'.$product->slug)->assertOk()->assertSee('Remove from Wishlist');
        $this->get('/account')->assertOk()->assertSee('Saved products');

        $this->actingAs($other)->get('/account/wishlist')->assertOk()->assertDontSee($product->title);
        $this->delete('/account/wishlist/'.$product->id)->assertRedirect();
        $this->assertDatabaseCount('wishlist_items', 1);
        $this->post('/account/wishlist/'.$product->id)->assertRedirect();
        $this->assertDatabaseCount('wishlist_items', 2);

        $this->actingAs($owner)->delete('/account/wishlist/'.$product->id)->assertRedirect();
        $this->assertDatabaseMissing('wishlist_items', ['user_id' => $owner->id, 'product_id' => $product->id]);
        $this->assertDatabaseHas('wishlist_items', ['user_id' => $other->id, 'product_id' => $product->id]);
    }

    public function test_hidden_product_cannot_be_saved_and_existing_unavailable_entry_can_be_removed(): void
    {
        [$product, $vendor, $category] = $this->listing('A private notebook listing');
        $owner = User::factory()->create(['role' => 'customer']);
        $this->actingAs($owner)->post('/account/wishlist/'.$product->id)->assertRedirect();

        $vendor->update(['status' => 'suspended']);
        $this->get('/account/wishlist')->assertOk()
            ->assertSee('Saved product unavailable')
            ->assertDontSee('A private notebook listing');
        $this->post('/account/wishlist/'.$product->id)->assertNotFound();
        $this->get('/en/product/'.$product->slug)->assertNotFound();
        $this->delete('/account/wishlist/'.$product->id)->assertRedirect();
        $this->assertDatabaseCount('wishlist_items', 0);

        $vendor->update(['status' => 'approved']);
        $category->update(['is_active' => false]);
        $this->post('/account/wishlist/'.$product->id)->assertNotFound();
        $category->update(['is_active' => true]);
        $product->update(['status' => 'draft']);
        $this->post('/account/wishlist/'.$product->id)->assertNotFound();
    }

    public function test_removing_a_product_cascades_to_saved_entries(): void
    {
        [$product] = $this->listing();
        $owner = User::factory()->create(['role' => 'customer']);
        $this->actingAs($owner)->post('/account/wishlist/'.$product->id)->assertRedirect();
        $product->delete();
        $this->assertDatabaseCount('wishlist_items', 0);
        $this->get('/account/wishlist')->assertOk()->assertSee('No saved products yet');
    }
}
