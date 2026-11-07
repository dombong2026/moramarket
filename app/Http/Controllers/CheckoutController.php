<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CheckoutController extends Controller
{
    public function form(Request $request)
    {
        $summary = app(CartController::class)->summary($request);
        abort_if($summary['items']->isEmpty(), 404, 'Cart is empty.');
        return view('storefront.checkout', $summary);
    }

    public function place(Request $request)
    {
        $data = $request->validate([
            'customer_name' => ['required', 'string', 'max:160'],
            'customer_email' => ['required', 'email', 'max:190'],
            'shipping_address' => ['required', 'string', 'min:10', 'max:2000'],
        ]);

        $cart = $request->session()->get('cart', []);
        if (empty($cart)) {
            return redirect()->route('cart.index')->withErrors(['cart' => 'Cart is empty.']);
        }

        $orderIds = DB::transaction(function () use ($cart, $data, $request) {
            $products = Product::query()->with(['vendor', 'category'])
                ->whereIn('id', array_keys($cart))->orderBy('id')->lockForUpdate()->get();

            if ($products->count() !== count($cart)) {
                throw ValidationException::withMessages(['cart' => 'A product has been removed. Please review your cart.']);
            }

            $groups = [];
            foreach ($products as $product) {
                $quantity = (int) $cart[$product->id];
                if (! $product->isPurchasable() || $quantity < 1 || $quantity > $product->stock) {
                    throw ValidationException::withMessages(['cart' => 'A product or quantity is unavailable. Please review your cart.']);
                }
                $groups[$product->vendor_id][] = [$product, $quantity];
            }

            $orderIds = [];
            foreach ($groups as $vendorId => $lines) {
                $cents = 0;
                foreach ($lines as [$product, $quantity]) {
                    $cents += (int) round(((float) $product->price) * 100) * $quantity;
                }
                $order = Order::create([
                    'number' => (string) Str::uuid(),
                    'user_id' => $request->user()->id,
                    'vendor_id' => $vendorId,
                    'status' => 'pending',
                    'payment_method' => 'cod',
                    'payment_status' => 'unpaid',
                    'customer_name' => $data['customer_name'],
                    'customer_email' => $data['customer_email'],
                    'shipping_address' => $data['shipping_address'],
                    'total' => $cents / 100,
                ]);

                foreach ($lines as [$product, $quantity]) {
                    $unitCents = (int) round(((float) $product->price) * 100);
                    $order->items()->create([
                        'product_id' => $product->id,
                        'title_snapshot' => $product->title,
                        'unit_price' => $unitCents / 100,
                        'quantity' => $quantity,
                        'line_total' => ($unitCents * $quantity) / 100,
                    ]);
                    $product->decrement('stock', $quantity);
                }
                $orderIds[] = $order->id;
            }

            return $orderIds;
        }, attempts: 3);

        $request->session()->forget('cart');
        return redirect()->route('account.orders')->with('success', count($orderIds).' order(s) placed. Pay on delivery.');
    }
}
