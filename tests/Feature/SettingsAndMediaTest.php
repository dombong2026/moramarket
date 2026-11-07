<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\SiteSetting;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SettingsAndMediaTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_administrators_can_edit_store_settings(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($customer)->get('/dashboard/settings')->assertForbidden();
        $this->actingAs($customer)->patch('/dashboard/settings', [
            'site_name' => 'Hijacked Store',
            'site_tagline' => 'Do not save',
        ])->assertForbidden();

        $this->actingAs($admin)->get('/dashboard/settings')->assertOk();
        $this->patch('/dashboard/settings', [
            'site_name' => 'Market One',
            'site_tagline' => 'Discover fresh ideas and quality products',
            'support_email' => 'support@example.test',
        ])->assertRedirect();

        $this->assertDatabaseHas('site_settings', ['key' => 'site_name', 'value' => 'Market One']);
        $this->get('/en')->assertOk()->assertSee('Market One');
    }

    public function test_appearance_controls_hero_and_footer_columns(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin)->patch('/dashboard/appearance', [
            'hero_heading' => 'Find your next favorite product',
            'hero_description' => 'Explore quality everyday goods from independent stores.',
            'footer_brand_enabled' => '1',
            'footer_market_enabled' => '1',
            'footer_account_enabled' => '0',
            'footer_languages_enabled' => '0',
        ])->assertRedirect();

        $this->assertSame('0', SiteSetting::valueOf('footer_languages_enabled'));
        $this->get('/en')->assertOk()
            ->assertSee('Find your next favorite product')
            ->assertDontSee('<h3>Languages</h3>', false);
    }

    public function test_images_are_delivered_without_a_public_storage_symlink_and_draft_images_are_private(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('products/preview.png', 'file-bytes');

        $seller = User::factory()->create(['role' => 'vendor']);
        $other = User::factory()->create(['role' => 'vendor']);
        $vendor = Vendor::create(['user_id' => $seller->id, 'name' => 'Secure Shop', 'slug' => 'secure-shop', 'status' => 'approved']);
        Vendor::create(['user_id' => $other->id, 'name' => 'Other Shop', 'slug' => 'other-shop', 'status' => 'approved']);
        $category = Category::create(['name' => 'Accessories', 'slug' => 'accessories', 'is_active' => true]);

        $product = Product::create([
            'vendor_id' => $vendor->id,
            'category_id' => $category->id,
            'title' => 'Private Draft',
            'slug' => 'private-draft',
            'price' => 10,
            'stock' => 5,
            'type' => 'physical',
            'status' => 'draft',
            'image_path' => 'products/preview.png',
        ]);

        $this->get('/listing-image/'.$product->id)->assertNotFound();
        $this->actingAs($other)->get('/listing-image/'.$product->id)->assertNotFound();
        $this->actingAs($seller)->get('/listing-image/'.$product->id)->assertOk();

        $product->update(['status' => 'published']);
        $this->get('/listing-image/'.$product->id)->assertOk();
    }

    public function test_system_health_is_administrator_only(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);
        $this->actingAs($customer)->get('/dashboard/system-health')->assertForbidden();

        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin)->get('/dashboard/system-health')
            ->assertOk()
            ->assertSee('Database connected');
    }
}
