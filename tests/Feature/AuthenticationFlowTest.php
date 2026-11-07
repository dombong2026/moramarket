<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Vendor;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class AuthenticationFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_all_four_guest_auth_pages_render_without_storefront_chrome(): void
    {
        $endpoints=[
            ['/login','Sign In','login.submit'],
            ['/register','Create Account','register.submit'],
            ['/forgot-password','Reset Password','password.email'],
            ['/reset-password/test-token?email=test%40example.test','Set New Password','password.update'],
        ];

        foreach($endpoints as [$path,$heading,$action]){
            $this->get($path)->assertOk()
                ->assertSee($heading)
                ->assertSee('auth-panel')
                ->assertSee('float-field')
                ->assertSee('name="_token"',false)
                ->assertSee('action="'.route($action).'"',false)
                ->assertSee('Back to Marketplace')
                ->assertDontSee('site-header')
                ->assertDontSee('site-footer')
                ->assertHeader('Cache-Control','no-store, private');
        }

        $this->get('/login')->assertSee('data-password-toggle="login-password"',false);
        $this->get('/register')->assertSee('data-password-toggle="register-confirm"',false);
        $this->get('/reset-password/test-token')->assertSee('data-password-toggle="reset-confirm"',false);
    }

    public function test_registration_normalizes_email_and_never_accepts_client_role(): void
    {
        $this->post('/register',[
            'name'=>'New Buyer',
            'email'=>'  Buyer@Example.TEST  ',
            'password'=>'StrongPassword123',
            'password_confirmation'=>'StrongPassword123',
            'role'=>'admin',
        ])->assertRedirect('/account');

        $this->assertAuthenticated();
        $user=User::where('email','buyer@example.test')->firstOrFail();
        $this->assertSame('customer',$user->role);
        $this->assertTrue(Hash::check('StrongPassword123',$user->password));
        $this->assertNotSame('StrongPassword123',$user->password);
        $this->get('/dashboard')->assertForbidden();
    }

    public function test_duplicate_email_case_variants_cannot_register_twice(): void
    {
        User::factory()->create(['email'=>'customer@example.test']);
        $this->post('/register',[
            'name'=>'Another Buyer',
            'email'=>'Customer@Example.TEST',
            'password'=>'StrongPassword123',
            'password_confirmation'=>'StrongPassword123',
        ])->assertSessionHasErrors('email');

        $this->assertSame(1,User::count());
        $this->assertGuest();
    }

    public function test_registration_rejects_mismatched_or_weak_password(): void
    {
        $this->post('/register',[
            'name'=>'Example','email'=>'person@example.test',
            'password'=>'password','password_confirmation'=>'another',
        ])->assertSessionHasErrors('password');

        $this->assertDatabaseMissing('users',['email'=>'person@example.test']);
    }

    public function test_login_credentials_are_checked_and_email_is_normalized(): void
    {
        $user=User::factory()->create([
            'role'=>'customer','email'=>'buyer@example.test','password'=>'StrongPassword123',
        ]);
        $this->post('/login',[
            'email'=>'buyer@example.test','password'=>'BadPassword123',
        ])->assertSessionHasErrors('email');
        $this->assertGuest();

        $this->post('/login',[
            'email'=>'  BUYER@EXAMPLE.TEST  ','password'=>'StrongPassword123',
            'remember'=>'1',
        ])->assertRedirect('/account');
        $this->assertAuthenticatedAs($user);
        $this->assertNotNull($user->fresh()->remember_token);
    }

    public function test_login_sends_admin_vendor_and_customer_to_correct_workspaces(): void
    {
        $admin=User::factory()->create(['role'=>'admin','password'=>'StrongPassword123']);
        $this->post('/login',[
            'email'=>$admin->email,'password'=>'StrongPassword123',
        ])->assertRedirect('/dashboard');
        $this->post('/logout')->assertRedirect('/');

        $vendor=User::factory()->create(['role'=>'vendor','password'=>'StrongPassword123']);
        Vendor::create([
            'user_id'=>$vendor->id,
            'name'=>'Approved Vendor',
            'slug'=>'approved-vendor',
            'status'=>'approved',
        ]);
        $this->post('/login',[
            'email'=>$vendor->email,'password'=>'StrongPassword123',
        ])->assertRedirect('/vendor');
        $this->post('/logout')->assertRedirect('/');

        $buyer=User::factory()->create(['role'=>'customer','password'=>'StrongPassword123']);
        $this->post('/login',[
            'email'=>$buyer->email,'password'=>'StrongPassword123',
        ])->assertRedirect('/account');
    }

    public function test_signed_in_users_never_get_admin_only_redirect_from_guest_pages(): void
    {
        $admin=User::factory()->create(['role'=>'admin']);
        $vendor=User::factory()->create(['role'=>'vendor']);
        Vendor::create([
            'user_id'=>$vendor->id,'name'=>'Vendor Shop',
            'slug'=>'vendor-shop','status'=>'approved',
        ]);
        $buyer=User::factory()->create(['role'=>'customer']);
        $unapproved=User::factory()->create(['role'=>'vendor']);
        Vendor::create([
            'user_id'=>$unapproved->id,'name'=>'Pending Shop',
            'slug'=>'pending-shop','status'=>'pending',
        ]);

        foreach([
            [$admin,'/dashboard'],[$vendor,'/vendor'],
            [$buyer,'/account'],[$unapproved,'/account'],
        ] as [$user,$target]){
            foreach(['/login','/register','/forgot-password','/reset-password/sample'] as $path){
                $this->actingAs($user)->get($path)->assertRedirect($target);
            }
        }
    }

    public function test_logout_invalidates_access_and_guest_cannot_invoke_logout(): void
    {
        $user=User::factory()->create(['role'=>'customer']);
        $this->actingAs($user)->post('/logout')->assertRedirect('/');
        $this->assertGuest();
        $this->get('/account')->assertRedirect('/login');
        $this->post('/logout')->assertRedirect('/login');
    }

    public function test_password_reset_shows_unavailable_status_when_mail_is_not_configured(): void
    {
        config()->set('mail.default','log');
        $this->get('/forgot-password')->assertOk()->assertSee('not available');
        $this->post('/forgot-password',['email'=>'buyer@example.test'])
            ->assertSessionHasErrors('email');
    }

    public function test_password_reset_link_uses_notification_delivery_when_configured(): void
    {
        config()->set('mail.default','smtp');
        Notification::fake();
        $user=User::factory()->create(['email'=>'buyer@example.test']);

        $this->post('/forgot-password',[
            'email'=>'  BUYER@EXAMPLE.TEST ',
        ])->assertRedirect()->assertSessionHas('status');

        Notification::assertSentTo($user,ResetPassword::class);
        $this->post('/forgot-password',[
            'email'=>'missing@example.test',
        ])->assertRedirect()->assertSessionHas('status');
        Notification::assertCount(1);
    }

    public function test_valid_reset_token_changes_password_and_cannot_be_reused(): void
    {
        $user=User::factory()->create([
            'email'=>'buyer@example.test',
            'password'=>'OldPassword123',
            'remember_token'=>'initialremembertoken',
        ]);
        $token=Password::broker()->createToken($user);
        $this->get('/reset-password/'.$token.'?email=BUYER%40EXAMPLE.TEST')
            ->assertOk()->assertSee('Set New Password')
            ->assertSee('name="token" value="'.$token.'"',false);

        $data=[
            'token'=>$token,
            'email'=>'BUYER@EXAMPLE.TEST',
            'password'=>'NewStrongPassword123',
            'password_confirmation'=>'NewStrongPassword123',
        ];
        $this->post('/reset-password',$data)->assertRedirect('/login');

        $this->assertTrue(Hash::check('NewStrongPassword123',$user->fresh()->password));
        $this->assertNotSame('initialremembertoken',$user->fresh()->remember_token);
        $this->post('/reset-password',$data)->assertSessionHasErrors('email');
    }

    public function test_invalid_reset_token_does_not_update_password(): void
    {
        $user=User::factory()->create([
            'email'=>'buyer@example.test',
            'password'=>'OldPassword123',
        ]);
        $this->post('/reset-password',[
            'token'=>'definitely-invalid',
            'email'=>$user->email,
            'password'=>'NewStrongPassword123',
            'password_confirmation'=>'NewStrongPassword123',
        ])->assertSessionHasErrors('email');
        $this->assertTrue(Hash::check('OldPassword123',$user->fresh()->password));
    }
}
