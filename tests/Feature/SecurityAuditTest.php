<?php

namespace Tests\Feature;

use App\Models\AuditEvent;
use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SecurityAuditTest extends TestCase
{
    use RefreshDatabase;

    public function test_audit_records_model_mutations_without_storing_private_values(): void
    {
        $admin=User::factory()->create(['role'=>'admin']);
        $this->actingAs($admin)->post('/dashboard/categories',[
            'name'=>'Home Electronics',
            'description'=>'Confidential inventory notes should never appear in the audit',
            'is_active'=>1,
        ])->assertRedirect();

        $category=Category::firstOrFail();
        $created=AuditEvent::where('subject_type',Category::class)
            ->where('subject_id',$category->id)->where('action','created')->firstOrFail();
        $this->assertSame($admin->id,$created->actor_id);
        $this->assertSame([],$created->changed_fields);

        $this->patch('/dashboard/categories/'.$category->id,[
            'name'=>'Consumer Electronics','description'=>'Secret business term',
            'is_active'=>1,
        ])->assertRedirect();

        $updated=AuditEvent::where('subject_type',Category::class)
            ->where('subject_id',$category->id)->where('action','updated')->firstOrFail();
        $this->assertContains('name',$updated->changed_fields);
        $this->assertStringNotContainsString('Secret business term',json_encode($updated->toArray()));
        $this->assertStringNotContainsString('Confidential inventory notes',json_encode($updated->toArray()));
    }

    public function test_security_center_is_admin_only_and_lists_real_events(): void
    {
        $admin=User::factory()->create(['role'=>'admin']);
        $customer=User::factory()->create(['role'=>'customer']);
        Category::create(['name'=>'Accessories','slug'=>'accessories','is_active'=>true]);

        $this->actingAs($customer)->get('/dashboard/security-center')->assertForbidden();
        $this->actingAs($admin)->get('/dashboard/security-center')->assertOk()
            ->assertSee('Security Center')->assertSee('Category')
            ->assertSee('10 per page')->assertSee('Refresh Activity')
            ->assertSee('aria-haspopup="menu"',false);
        $this->get('/dashboard/security-center?status=created')->assertOk();
        $this->get('/dashboard/security-center?status=invalid')->assertSessionHasErrors('status');
    }

    public function test_successful_login_generates_an_audit_event(): void
    {
        $user=User::factory()->create(['role'=>'customer','password'=>'LoginPassword123']);
        $this->post('/login',[
            'email'=>$user->email,
            'password'=>'LoginPassword123',
        ])->assertRedirect('/account');

        $this->assertDatabaseHas('audit_events',[
            'subject_type'=>User::class,
            'subject_id'=>$user->id,
            'action'=>'signed_in',
        ]);
    }
}
