<?php

namespace Tests\Feature;

use App\Models\ContentEntry;
use App\Models\MediaAsset;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class EditorialViewsVideoTest extends TestCase
{
    use RefreshDatabase;

    public function test_all_new_dashboard_and_public_pages_render(): void
    {
        $admin=User::factory()->create(['role'=>'admin']);
        $vendor=User::factory()->create(['role'=>'vendor']);
        Vendor::create([
            'user_id'=>$vendor->id,'name'=>'Video Goods','slug'=>'video-goods','status'=>'approved',
        ]);
        $customer=User::factory()->create(['role'=>'customer']);

        $this->get('/support-center')->assertOk();
        $this->get('/policies')->assertOk();
        $this->get('/en/blog')->assertOk();
        $this->get('/en/pages')->assertOk();

        $this->actingAs($admin)->get('/dashboard/content/page')->assertOk()
            ->assertSee('Page Manager')->assertSee('HTML');
        $this->get('/dashboard/content/post')->assertOk()->assertSee('Post Manager');
        $this->get('/dashboard/policies')->assertOk()->assertSee('Policy Center');
        $this->get('/dashboard/knowledge')->assertOk()->assertSee('Knowledge Base');
        $this->get('/dashboard/support')->assertOk()->assertSee('Support Center');
        $this->actingAs($vendor)->get('/vendor/shipping')->assertOk()
            ->assertSee('Delivery Settings');
        $this->actingAs($customer)->get('/account/support')->assertOk()
            ->assertSee('My Support Tickets');
    }

    public function test_video_upload_is_scoped_and_can_be_used_as_published_content_feature(): void
    {
        Storage::fake('local');
        $admin=User::factory()->create(['role'=>'admin']);
        $customer=User::factory()->create(['role'=>'customer']);

        $this->actingAs($admin)->post('/dashboard/media',[
            'file'=>UploadedFile::fake()->create('walkthrough.mp4',250,'video/mp4'),
            'alt_text'=>'A product walkthrough',
        ])->assertRedirect();

        $asset=MediaAsset::firstOrFail();
        $this->assertSame('video/mp4',$asset->mime_type);
        Storage::disk('local')->assertExists($asset->path);
        $this->actingAs($customer)->get('/media-preview/'.$asset->id)->assertNotFound();

        $this->actingAs($admin)->post('/dashboard/content/post',[
            'title'=>'Video product announcement',
            'body_html'=>'<p>Watch the new marketplace walkthrough.</p>',
            'status'=>'draft',
            'featured_type'=>'video',
            'featured_media_id'=>$asset->id,
        ])->assertRedirect();

        $entry=ContentEntry::firstOrFail();
        $this->get('/content-media/post/'.$entry->id)->assertNotFound();
        $this->patch('/dashboard/content/post/'.$entry->id,[
            'title'=>'Video product announcement',
            'body_html'=>'<p>Watch the new marketplace walkthrough.</p>',
            'status'=>'published',
            'featured_type'=>'video',
            'featured_media_id'=>$asset->id,
        ])->assertRedirect();

        $this->get('/en/post/'.$entry->slug)->assertOk()->assertSee('<video',false);
        $this->get('/content-media/post/'.$entry->id)->assertOk();
        $this->delete('/dashboard/media/'.$asset->id)->assertSessionHasErrors('file');
    }

    public function test_customer_cannot_administer_policy_or_publish_editorial_content(): void
    {
        $customer=User::factory()->create(['role'=>'customer']);
        $this->actingAs($customer)->post('/dashboard/content/post',[
            'title'=>'Malicious post','body_html'=>'<p>Malicious</p>',
            'status'=>'published','featured_type'=>'none',
        ])->assertForbidden();
        $this->post('/dashboard/policies',[
            'title'=>'Fake policy','body_html'=>'<p>Fake</p>','status'=>'published',
        ])->assertForbidden();
        $this->post('/dashboard/knowledge/categories',[
            'name'=>'Fake category',
        ])->assertForbidden();
    }
}
