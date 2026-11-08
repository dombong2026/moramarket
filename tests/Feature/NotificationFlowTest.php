<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\SupportTicket;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_support_notifications_are_private_and_admin_updates_reach_customer(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $customer = User::factory()->create(['role' => 'customer']);
        $stranger = User::factory()->create(['role' => 'customer']);

        $this->get('/account/notifications')->assertRedirect('/login');

        $this->actingAs($customer)->post('/account/support', [
            'subject' => 'My package is delayed',
            'message' => 'Please check the delivery status for my parcel.',
        ])->assertRedirect();

        $ticket = SupportTicket::firstOrFail();
        $adminNote = $admin->notifications()->firstOrFail();
        $this->assertSame(1, $admin->unreadNotifications()->count());
        $this->assertSame(0, $customer->notifications()->count());
        $this->assertSame('/dashboard/support/'.$ticket->id, $adminNote->data['path']);

        $this->actingAs($stranger)->get('/account/notifications')->assertOk()
            ->assertDontSee('My package is delayed');
        $this->post('/account/notifications/'.$adminNote->id.'/read')->assertNotFound();

        $this->actingAs($admin)->get('/account/notifications?status=unread')->assertOk()
            ->assertSee('New support ticket')
            ->assertSee('My package is delayed');
        $this->post('/account/notifications/'.$adminNote->id.'/read')
            ->assertRedirect();
        $this->assertSame(0, $admin->unreadNotifications()->count());

        $this->post('/dashboard/support/'.$ticket->id.'/reply', [
            'message' => 'Your delivery partner is checking the parcel.',
        ])->assertRedirect();
        $this->assertSame(1, $customer->unreadNotifications()->count());
        $this->actingAs($customer)->get('/account/notifications')
            ->assertOk()->assertSee('Support replied')
            ->assertSee('View related item');

        $this->post('/account/support/'.$ticket->id.'/reply', [
            'message' => 'Thank you, I will check tomorrow.',
        ])->assertRedirect();
        $this->assertSame(1, $admin->unreadNotifications()->count());

        $this->actingAs($admin)->patch('/dashboard/support/'.$ticket->id.'/status', [
            'status' => 'closed',
        ])->assertRedirect();
        $this->assertSame(2, $customer->unreadNotifications()->count());

        $this->actingAs($customer)->post('/account/notifications/read-all')
            ->assertRedirect();
        $this->assertSame(0, $customer->unreadNotifications()->count());
        $this->get('/account/notifications?status=read')->assertOk()
            ->assertSee('Support replied')->assertSee('Support ticket updated');
    }

    public function test_order_checkout_notifies_approved_vendor_and_valid_status_change_notifies_buyer(): void
    {
        $seller = User::factory()->create(['role' => 'vendor']);
        $vendor = Vendor::create([
            'user_id' => $seller->id,
            'name' => 'Market Studio',
            'slug' => 'market-studio',
            'status' => 'approved',
        ]);
        $buyer = User::factory()->create(['role' => 'customer']);
        $outsider = User::factory()->create(['role' => 'customer']);
        $category = Category::create([
            'name' => 'Accessories',
            'slug' => 'accessories',
            'is_active' => true,
        ]);
        $product = Product::create([
            'vendor_id' => $vendor->id,
            'category_id' => $category->id,
            'title' => 'Reusable water bottle',
            'slug' => 'reusable-water-bottle',
            'type' => 'physical',
            'status' => 'published',
            'price' => 8,
            'stock' => 5,
        ]);
        $this->actingAs($buyer)->post('/cart/'.$product->id, ['quantity' => 1])
            ->assertRedirect();
        $this->post('/checkout', [
            'customer_name' => 'Buyer',
            'customer_email' => 'buyer@example.test',
            'shipping_address' => 'Street 12, Battambang, Cambodia',
        ])->assertRedirect('/account/orders');

        $order = Order::firstOrFail();
        $this->assertSame(1, $seller->unreadNotifications()->count());
        $this->assertSame(0, $buyer->unreadNotifications()->count());
        $this->actingAs($seller)->get('/account/notifications')->assertOk()
            ->assertSee('New store order');
        $this->patch('/vendor/orders/'.$order->id, ['status' => 'processing'])
            ->assertRedirect();
        $this->assertSame(1, $buyer->unreadNotifications()->count());
        $this->actingAs($buyer)->get('/account/notifications')->assertOk()
            ->assertSee('Order status updated');

        $this->actingAs($outsider)->get('/account/notifications')->assertOk()
            ->assertDontSee('Order status updated');
        $this->actingAs($seller)->patch('/vendor/orders/'.$order->id, ['status' => 'completed'])
            ->assertSessionHasErrors('status');
        $this->assertSame(1, $buyer->unreadNotifications()->count());
    }

    public function test_notification_filters_and_owner_only_mutations_are_validated(): void
    {
        $owner = User::factory()->create(['role' => 'customer']);
        $stranger = User::factory()->create(['role' => 'customer']);
        $owner->notify(new \App\Notifications\MarketplaceNotice(
            'Test event', 'An internal test notification', route('account.orders', [], false),
        ));
        $id = $owner->notifications()->firstOrFail()->id;

        $this->actingAs($stranger)->post('/account/notifications/'.$id.'/read')->assertNotFound();
        $this->actingAs($owner)->get('/account/notifications?status=broken')
            ->assertSessionHasErrors('status');
        $this->get('/account/notifications?status=unread')->assertOk()
            ->assertSee('Test event');
        $this->post('/account/notifications/'.$id.'/read')->assertRedirect();
        $this->assertNotNull($owner->notifications()->firstOrFail()->read_at);
        $this->get('/account/notifications?status=unread')->assertOk()
            ->assertDontSee('An internal test notification');
    }
}
