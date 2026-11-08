<?php
/**
 * Browser-only fixtures. Never run against a customer or public website.
 * Usage: APP_ENV=testing CI=true GITHUB_ACTIONS=true php support/qa-visual-fixtures.php
 */
declare(strict_types=1);

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
use App\Notifications\MarketplaceNotice;
use Illuminate\Contracts\Console\Kernel;

require dirname(__DIR__).'/vendor/autoload.php';
$app = require dirname(__DIR__).'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$db = (string)config('database.connections.sqlite.database');
$temp = realpath((string)getenv('RUNNER_TEMP'));
$actual = realpath($db);
if (getenv('CI') !== 'true' || getenv('GITHUB_ACTIONS') !== 'true'
    || app()->environment() !== 'testing'
    || config('database.default') !== 'sqlite'
    || !$temp || !$actual || !str_starts_with($actual, $temp.DIRECTORY_SEPARATOR)) {
    fwrite(STDERR, "VISUAL_FIXTURES=BLOCKED_NON_ISOLATED_RUNTIME\n");
    exit(1);
}

$password = 'VisualAcceptance123';
$admin = User::create(['name'=>'QA Administrator','email'=>'admin@visual-qa.test','password'=>$password,'role'=>'admin']);
$seller = User::create(['name'=>'QA Vendor','email'=>'vendor@visual-qa.test','password'=>$password,'role'=>'vendor']);
$buyer = User::create(['name'=>'QA Customer','email'=>'customer@visual-qa.test','password'=>$password,'role'=>'customer']);
$vendor = Vendor::create(['user_id'=>$seller->id,'name'=>'QA Example Store','slug'=>'qa-example-store','status'=>'approved']);
$category = Category::query()->where('slug','electronics')->first()
    ?: Category::create(['name'=>'Electronics','slug'=>'electronics','is_active'=>true]);
$product = Product::create([
    'vendor_id'=>$vendor->id,'category_id'=>$category->id,
    'title'=>'QA Bluetooth Speaker','slug'=>'qa-bluetooth-speaker','type'=>'physical',
    'price'=>'15.99','stock'=>10,'status'=>'published',
    'description'=>'An independent test fixture for visual acceptance.',
]);
$order = Order::create([
    'number'=>'qa-visual-0001','user_id'=>$buyer->id,'vendor_id'=>$vendor->id,
    'status'=>'pending','payment_method'=>'cod','payment_status'=>'unpaid',
    'customer_name'=>'QA Customer','customer_email'=>'customer@visual-qa.test',
    'shipping_address'=>'Example Street, Battambang, Cambodia',
    'subtotal'=>15.99,'shipping_fee'=>0,'total'=>15.99,
]);
$order->items()->create([
    'product_id'=>$product->id,'title_snapshot'=>$product->title,
    'unit_price'=>15.99,'quantity'=>1,'line_total'=>15.99,
]);
foreach (['page','post'] as $type) {
    ContentEntry::create([
        'author_id'=>$admin->id,'type'=>$type,'title'=>"QA sample $type",
        'slug'=>"qa-sample-$type",'body_html'=>"<p>QA $type body content</p>",
        'status'=>'published','published_at'=>now(),'revision'=>1,'featured_type'=>'none',
    ]);
}
Policy::create([
    'editor_id'=>$admin->id,'title'=>'QA Sample Policy','slug'=>'qa-sample-policy',
    'body_html'=>'<p>Test policy content</p>','status'=>'published',
    'published_at'=>now(),'revision'=>1,
]);
$knowledgeCategory = KnowledgeCategory::create(['name'=>'QA Help','slug'=>'qa-help']);
KnowledgeArticle::create([
    'category_id'=>$knowledgeCategory->id,'author_id'=>$admin->id,
    'title'=>'QA Help Article','slug'=>'qa-help-article',
    'body_html'=>'<p>Help content for browser acceptance</p>',
    'status'=>'published','published_at'=>now(),
]);
$ticket = SupportTicket::create([
    'user_id'=>$buyer->id,'subject'=>'QA Order Inquiry','status'=>'open',
]);
$ticket->messages()->create(['user_id'=>$buyer->id,'body'=>'This message exists only in isolated QA.']);
$admin->notify(new MarketplaceNotice('QA support inquiry','A new QA support conversation exists.', '/dashboard/support/'.$ticket->id));
$seller->notify(new MarketplaceNotice('QA store order','QA customer placed an order.', '/vendor/orders'));
$buyer->notify(new MarketplaceNotice('QA customer notice','QA order was received.', '/account/orders'));
echo "VISUAL_FIXTURES=READY\n";
