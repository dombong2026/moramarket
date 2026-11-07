<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\User;
use App\Models\Vendor;
use App\Services\SafeHtml;
use App\Support\TableQuery;
use App\Support\WorkspaceNavigation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class PlatformFoundationTest extends TestCase
{
    use RefreshDatabase;

    public function test_all_controller_routes_resolve_to_real_classes_and_methods(): void
    {
        foreach (\Illuminate\Support\Facades\Route::getRoutes() as $route) {
            $action=$route->getActionName();
            if (!str_contains($action,'@')) {
                continue;
            }
            [$class,$method]=explode('@',$action,2);
            $this->assertTrue(class_exists($class),'Missing controller: '.$class);
            $this->assertTrue(method_exists($class,$method),'Missing action: '.$action);
        }
    }

    public function test_all_workspace_navigation_entries_have_distinct_real_route_urls(): void
    {
        foreach (['admin','vendor','account'] as $workspace) {
            $items=WorkspaceNavigation::for($workspace,Request::create('/'));
            $this->assertNotEmpty($items);
            $this->assertSame(count($items),count(array_unique(array_column($items,'url'))));
            foreach ($items as $item) {
                $this->assertStringStartsWith('http',$item['url']);
                $this->assertNotSame('#',$item['url']);
                $this->assertNotSame('',trim($item['label']));
            }
        }
    }

    public function test_all_three_dashboards_reuse_the_same_shell_with_role_navigation(): void
    {
        $admin=User::factory()->create(['role'=>'admin']);
        $vendor=User::factory()->create(['role'=>'vendor']);
        Vendor::create(['user_id'=>$vendor->id,'name'=>'Platform Shop','slug'=>'platform-shop','status'=>'approved']);
        $customer=User::factory()->create(['role'=>'customer']);

        $this->actingAs($admin)->get('/dashboard')->assertOk()
            ->assertSee('workspace-shell')->assertSee('Page Manager')
            ->assertSee('System Health')->assertDontSee('href="#"',false);
        $this->actingAs($vendor)->get('/vendor')->assertOk()
            ->assertSee('workspace-shell')->assertSee('Delivery Settings')
            ->assertDontSee('Page Manager');
        $this->actingAs($customer)->get('/account')->assertOk()
            ->assertSee('workspace-shell')->assertSee('Support Tickets')
            ->assertDontSee('System Health');
    }

    public function test_table_ui_has_single_pagination_contract_and_accessible_actions(): void
    {
        $admin=User::factory()->create(['role'=>'admin']);
        foreach (range(1,23) as $number) {
            Category::create([
                'name'=>sprintf('Category %02d',$number),
                'slug'=>'category-'.$number,
                'is_active'=>$number<=13,
            ]);
        }

        $this->assertSame(10,TableQuery::PAGE_SIZE);
        $this->actingAs($admin)->get('/dashboard/categories')->assertOk()
            ->assertSee('Page 1 of 3')->assertSee('10 per page')
            ->assertSee('Go To')->assertSee('aria-haspopup="menu"',false);
        $this->get('/dashboard/categories?status=active')->assertOk()
            ->assertSee('Page 1 of 2');
        $this->get('/dashboard/categories?q=Category+23')->assertOk()
            ->assertSee('Category 23')->assertDontSee('Category 22');
        $this->get('/dashboard/categories?status=unknown')->assertSessionHasErrors('status');
    }

    public function test_failed_form_validation_remembers_centered_modal(): void
    {
        $admin=User::factory()->create(['role'=>'admin']);
        $this->actingAs($admin)->post('/dashboard/content/page',[
            'title'=>'','body_html'=>'','status'=>'draft','featured_type'=>'none',
            '_modal'=>'content-create',
        ])->assertSessionHasErrors(['title','body_html']);

        $this->get('/dashboard/content/page')->assertOk()
            ->assertSee('data-modal="content-create"',false)
            ->assertSee('id="mm-global-confirm"',false);
    }

    public function test_common_security_headers_apply_to_public_and_private_pages(): void
    {
        $this->get('/en')->assertOk()
            ->assertHeader('X-Content-Type-Options','nosniff')
            ->assertHeader('X-Frame-Options','DENY')
            ->assertHeader('Referrer-Policy','strict-origin-when-cross-origin')
            ->assertHeader('Content-Security-Policy');
        $this->get('/login')->assertOk()->assertHeader('Cache-Control','no-store, private');
        $this->withCookie('mm-theme','dark')->get('/login')
            ->assertOk()->assertSee('data-theme="dark"',false);
    }

    public function test_the_shared_sanitizer_rejects_executable_markup(): void
    {
        $safe=(new SafeHtml())->clean('<h2 onclick="alert(1)">Hello</h2>'.
            '<a href="javascript:alert(1)">Unsafe</a>'.
            '<a href="https://example.com/help" onmouseover="alert(1)">Safe</a>'.
            '<svg onload="alert(1)"><circle></circle></svg>'.
            '<iframe src="https://example.com"></iframe><p>Text</p>');
        $this->assertStringContainsString('<h2>Hello</h2>',$safe);
        $this->assertStringContainsString('https://example.com/help',$safe);
        $this->assertStringContainsString('<p>Text</p>',$safe);
        foreach (['onclick','onmouseover','javascript:','<svg','<iframe'] as $blocked) {
            $this->assertStringNotContainsString($blocked,$safe);
        }
    }
}
