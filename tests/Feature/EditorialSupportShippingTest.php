<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\ContentEntry;
use App\Models\KnowledgeArticle;
use App\Models\KnowledgeCategory;
use App\Models\Order;
use App\Models\Policy;
use App\Models\Product;
use App\Models\SupportTicket;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EditorialSupportShippingTest extends TestCase
{
    use RefreshDatabase;

    private function vendor(string $slug='fresh'): User
    {
        $seller=User::factory()->create(['role'=>'vendor']);
        Vendor::create([
            'user_id'=>$seller->id,'name'=>'Fresh Store','slug'=>$slug,'status'=>'approved',
        ]);
        return $seller;
    }

    public function test_draft_page_is_private_published_page_is_visible_and_html_is_sanitized(): void
    {
        $admin=User::factory()->create(['role'=>'admin']);
        $customer=User::factory()->create();
        $this->actingAs($customer)->get('/dashboard/content/page')->assertForbidden();
        $this->actingAs($admin)->post('/dashboard/content/page',[
            'title'=>'About Our Marketplace',
            'body_html'=>'<p>Welcome <strong>friends</strong></p><script>alert(1)</script><a href="javascript:alert(1)" onclick="alert(1)">unsafe</a>',
            'status'=>'draft','featured_type'=>'none',
        ])->assertRedirect();

        $entry=ContentEntry::firstOrFail();
        $this->assertSame('page',$entry->type);
        $this->assertStringNotContainsString('<script',$entry->body_html);
        $this->assertStringNotContainsString('javascript:', $entry->body_html);
        $this->assertStringNotContainsString('onclick', $entry->body_html);
        $this->get('/en/page/'.$entry->slug)->assertNotFound();

        $this->patch('/dashboard/content/page/'.$entry->id,[
            'title'=>'About Our Marketplace','body_html'=>'<h2>Our Store</h2><p>We welcome everyone.</p>',
            'status'=>'published','featured_type'=>'color','featured_color'=>'#f97316',
        ])->assertRedirect();
        $this->get('/en/page/'.$entry->slug)->assertOk()->assertSee('Our Store');
        $this->get('/en/pages')->assertOk()->assertSee($entry->title);
        $this->assertSame(2,$entry->fresh()->revision);

        $this->post('/dashboard/content/page/'.$entry->id.'/restore/1')->assertRedirect();
        $this->assertSame(3,$entry->fresh()->revision);
        $this->assertSame('draft',$entry->fresh()->status);
        $this->get('/en/page/'.$entry->slug)->assertNotFound();
    }

    public function test_post_accepts_vetted_youtube_id_and_rejects_untrusted_hosts(): void
    {
        $admin=User::factory()->create(['role'=>'admin']);
        $this->actingAs($admin)->post('/dashboard/content/post',[
            'title'=>'A new launch',
            'body_html'=>'<p>Our marketplace has new products.</p>',
            'status'=>'published','featured_type'=>'youtube',
            'featured_youtube_id'=>'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
        ])->assertRedirect();

        $entry=ContentEntry::firstOrFail();
        $this->assertSame('dQw4w9WgXcQ',$entry->featured_youtube_id);
        $this->get('/en/post/'.$entry->slug)->assertOk()
            ->assertSee('youtube-nocookie.com/embed/dQw4w9WgXcQ');

        $this->post('/dashboard/content/post',[
            'title'=>'Unsafe video','body_html'=>'<p>Reject this URL</p>',
            'status'=>'published','featured_type'=>'youtube',
            'featured_youtube_id'=>'https://evil.example/embed/dQw4w9WgXcQ',
        ])->assertSessionHasErrors('featured_youtube_id');
        $this->assertSame(1,ContentEntry::count());
    }

    public function test_policy_drafts_are_hidden_and_published_policy_is_sanitized(): void
    {
        $admin=User::factory()->create(['role'=>'admin']);
        $this->actingAs($admin)->post('/dashboard/policies',[
            'title'=>'Privacy Policy','body_html'=>'<h2>Privacy</h2><p>We respect privacy.</p><img src=x onerror=alert(1)>',
            'status'=>'draft',
        ])->assertRedirect();

        $policy=Policy::firstOrFail();
        $this->get('/policies/'.$policy->slug)->assertNotFound();
        $this->assertStringNotContainsString('<img',$policy->body_html);
        $this->patch('/dashboard/policies/'.$policy->id,[
            'title'=>'Privacy Policy','body_html'=>'<h2>Privacy</h2><p>We respect privacy.</p>',
            'status'=>'published',
        ])->assertRedirect();
        $this->get('/policies/'.$policy->slug)->assertOk()->assertSee('We respect privacy.');
        $this->get('/policies')->assertOk()->assertSee('Privacy Policy');
        $this->assertSame(2,$policy->fresh()->revision);
    }

    public function test_knowledge_base_search_and_tickets_have_owner_guards(): void
    {
        $admin=User::factory()->create(['role'=>'admin']);
        $customer=User::factory()->create(['role'=>'customer']);
        $other=User::factory()->create(['role'=>'customer']);

        $this->actingAs($admin)->post('/dashboard/knowledge/categories',[
            'name'=>'Orders & Delivery',
        ])->assertRedirect();
        $category=KnowledgeCategory::firstOrFail();

        $this->post('/dashboard/knowledge',[
            'title'=>'How do I track my delivery?',
            'body_html'=>'<p>Sign in and open the order history.</p>',
            'category_id'=>$category->id,'status'=>'published',
        ])->assertRedirect();
        $article=KnowledgeArticle::firstOrFail();
        $this->get('/support-center?q=delivery')->assertOk()->assertSee($article->title);
        $this->get('/support-center/article/'.$article->slug)->assertOk()->assertSee('order history');
        $this->actingAs($customer)->get('/dashboard/knowledge')->assertForbidden();

        $this->post('/account/support',[
            'subject'=>'Delivery status inquiry',
            'message'=>'Can you confirm my expected delivery date?',
        ])->assertRedirect();
        $ticket=SupportTicket::firstOrFail();
        $this->assertSame(1,$ticket->messages()->count());
        $this->actingAs($other)->get('/account/support/'.$ticket->id)->assertNotFound();
        $this->post('/account/support/'.$ticket->id.'/reply',[
            'message'=>'Unauthorized attempt',
        ])->assertNotFound();

        $this->actingAs($admin)->get('/dashboard/support/'.$ticket->id)->assertOk();
        $this->post('/dashboard/support/'.$ticket->id.'/reply',[
            'message'=>'Please provide your order reference.',
        ])->assertRedirect();
        $this->assertSame(2,$ticket->messages()->count());
        $this->patch('/dashboard/support/'.$ticket->id.'/status',[
            'status'=>'closed',
        ])->assertRedirect();
        $this->actingAs($customer)->post('/account/support/'.$ticket->id.'/reply',[
            'message'=>'Can you help me with something else?',
        ])->assertStatus(422);
    }

    public function test_vendor_shipping_is_charged_per_order_and_status_transitions_are_guarded(): void
    {
        $seller=$this->vendor('hardware');
        $buyer=User::factory()->create(['role'=>'customer']);
        $category=Category::create(['name'=>'Electronics','slug'=>'electronics','is_active'=>true]);
        $product=Product::create([
            'vendor_id'=>$seller->vendor->id,'category_id'=>$category->id,
            'title'=>'Charging cable','slug'=>'charging-cable','type'=>'physical',
            'price'=>10,'stock'=>9,'status'=>'published',
        ]);

        $this->actingAs($seller)->patch('/vendor/shipping',[
            'enabled'=>1,'flat_rate'=>'2.50','free_above'=>'50.00',
            'estimated_days'=>4,'policy_note'=>'Delivery after dispatch.',
        ])->assertRedirect();
        $this->actingAs($buyer)->post('/cart/'.$product->id,['quantity'=>2])->assertRedirect();
        $this->get('/checkout')->assertOk()->assertSee('22.50');
        $this->post('/checkout',[
            'customer_name'=>'Buyer Name','customer_email'=>'buyer@example.test',
            'shipping_address'=>'Battambang City, Cambodia, Street 12',
        ])->assertRedirect('/account/orders');

        $order=Order::firstOrFail();
        $this->assertSame('20.00',$order->subtotal);
        $this->assertSame('2.50',$order->shipping_fee);
        $this->assertSame('22.50',$order->total);
        $this->actingAs($buyer)->patch('/vendor/orders/'.$order->id,[
            'status'=>'processing',
        ])->assertForbidden();
        $this->actingAs($seller)->patch('/vendor/orders/'.$order->id,[
            'status'=>'shipped',
        ])->assertSessionHasErrors('status');
        $this->patch('/vendor/orders/'.$order->id,['status'=>'processing'])->assertRedirect();
        $this->patch('/vendor/orders/'.$order->id,[
            'status'=>'shipped','shipping_carrier'=>'Local Courier','tracking_number'=>'MM-1234',
        ])->assertRedirect();
        $this->assertNotNull($order->fresh()->shipped_at);
        $this->patch('/vendor/orders/'.$order->id,['status'=>'completed'])->assertRedirect();
        $this->assertNotNull($order->fresh()->delivered_at);
        $this->patch('/vendor/orders/'.$order->id,['status'=>'cancelled'])->assertSessionHasErrors('status');
    }
}
