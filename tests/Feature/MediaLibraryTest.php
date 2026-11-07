<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\MediaAsset;
use App\Models\Product;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MediaLibraryTest extends TestCase
{
    use RefreshDatabase;

    private function approvedVendor(string $slug): User
    {
        $user = User::factory()->create(['role' => 'vendor']);
        Vendor::create([
            'user_id' => $user->id,
            'name' => ucfirst($slug).' Shop',
            'slug' => $slug,
            'status' => 'approved',
        ]);
        return $user;
    }

    public function test_vendor_upload_is_private_and_visible_only_to_owner_or_admin(): void
    {
        Storage::fake('local');
        $seller = $this->approvedVendor('first');
        $otherSeller = $this->approvedVendor('second');
        $admin = User::factory()->create(['role' => 'admin']);
        $customer = User::factory()->create(['role' => 'customer']);

        $this->actingAs($customer)->get('/vendor/media')->assertForbidden();

        $this->actingAs($seller)->post('/vendor/media', [
            'file' => UploadedFile::fake()->image('quality-product.png'),
            'alt_text' => 'Handmade ceramic bowl',
        ])->assertRedirect();

        $asset = MediaAsset::firstOrFail();
        $this->assertSame($seller->id, $asset->user_id);
        $this->assertSame('image/png', $asset->mime_type);
        Storage::disk('local')->assertExists($asset->path);

        $this->actingAs($otherSeller)->get('/media-preview/'.$asset->id)->assertNotFound();
        $this->actingAs($customer)->get('/media-preview/'.$asset->id)->assertNotFound();
        $this->actingAs($seller)->get('/media-preview/'.$asset->id)->assertOk();
        $this->actingAs($admin)->get('/dashboard/media')->assertOk()
            ->assertSee('quality-product.png');

        $this->actingAs($otherSeller)->patch('/vendor/media/'.$asset->id, [
            'original_name' => 'changed.png',
            'alt_text' => 'Unauthorized',
        ])->assertNotFound();

        $this->actingAs($seller)->patch('/vendor/media/'.$asset->id, [
            'original_name' => 'ceramic-bowl.png',
            'alt_text' => 'Ceramic bowl on wood',
        ])->assertRedirect();

        $this->assertDatabaseHas('media_assets', ['id' => $asset->id, 'alt_text' => 'Ceramic bowl on wood']);
    }

    public function test_vendor_can_select_media_for_a_product_and_deletion_is_guarded(): void
    {
        Storage::fake('local');
        $seller = $this->approvedVendor('store-one');
        $other = $this->approvedVendor('store-two');
        $category = Category::create(['name' => 'Home', 'slug' => 'home', 'is_active' => true]);

        $this->actingAs($seller)->post('/vendor/media', [
            'file' => UploadedFile::fake()->image('pot.png'),
            'alt_text' => 'Pot',
        ])->assertRedirect();

        $asset = MediaAsset::firstOrFail();

        $this->actingAs($other)->post('/vendor/products', [
            'title' => 'Other store pot',
            'category_id' => $category->id,
            'type' => 'physical',
            'price' => 15,
            'stock' => 2,
            'status' => 'published',
            'media_id' => $asset->id,
        ])->assertSessionHasErrors('media_id');

        $this->actingAs($seller)->post('/vendor/products', [
            'title' => 'Store ceramic pot',
            'category_id' => $category->id,
            'type' => 'physical',
            'price' => 15,
            'stock' => 2,
            'status' => 'published',
            'media_id' => $asset->id,
        ])->assertRedirect();

        $product = Product::firstOrFail();
        $this->assertSame($asset->id, $product->featured_media_id);
        $this->get('/listing-image/'.$product->id)->assertOk();

        $this->delete('/vendor/media/'.$asset->id)->assertSessionHasErrors('file');
        $this->assertDatabaseHas('media_assets', ['id' => $asset->id]);

        $this->patch('/vendor/products/'.$product->id, [
            'title' => 'Store ceramic pot',
            'category_id' => $category->id,
            'type' => 'physical',
            'price' => 15,
            'stock' => 2,
            'status' => 'draft',
            'remove_image' => 1,
        ])->assertRedirect();

        $this->assertNull($product->fresh()->featured_media_id);
        $this->delete('/vendor/media/'.$asset->id)->assertRedirect();
        $this->assertDatabaseMissing('media_assets', ['id' => $asset->id]);
        Storage::disk('local')->assertMissing($asset->path);
    }

    public function test_media_table_has_search_filter_and_ten_item_pagination(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        foreach (range(1, 13) as $n) {
            MediaAsset::create([
                'user_id' => $admin->id,
                'path' => "media/$n/image.png",
                'original_name' => "image-$n.png",
                'mime_type' => 'image/png',
                'size_bytes' => 1024,
            ]);
        }

        $this->actingAs($admin)->get('/dashboard/media?status=png')
            ->assertOk()->assertSee('10 per page')->assertSee('Page 1 of 2');
        $this->get('/dashboard/media?status=webp')
            ->assertOk()->assertSee('No images found');
        $this->get('/dashboard/media?q=image-7')
            ->assertOk()->assertSee('image-7.png');
    }
}
