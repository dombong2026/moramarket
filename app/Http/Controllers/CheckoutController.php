<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Product;
use App\Services\ShippingCalculator;
use App\Services\MarketplaceNotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CheckoutController extends Controller
{
    public function form(Request $request, ShippingCalculator $shipping)
    {
        $summary=app(CartController::class)->summary($request);
        abort_if($summary['items']->isEmpty(),404,'Cart is empty.');

        $groups=[];
        $names=[];
        foreach ($summary['items'] as $line) {
            $product=$line['product'];
            $groups[$product->vendor_id][]=[$product,$line['quantity']];
            $names[$product->vendor_id]=$product->vendor->name;
        }
        $breakdown=$shipping->breakdown($groups);

        if (collect($breakdown)->contains(fn ($row)=>!$row['enabled'])) {
            return redirect()->route('cart.index')
                ->withErrors(['shipping'=>'One of your vendors is not accepting delivery orders.']);
        }

        $shippingTotalCents=array_sum(array_column($breakdown,'shipping_cents'));
        $summary['shippingTotal']=$shippingTotalCents/100;
        $summary['grandTotal']=$summary['total']+$summary['shippingTotal'];
        $summary['vendorTotals']=$breakdown;
        $summary['vendorNames']=$names;

        return view('storefront.checkout',$summary);
    }

    public function place(Request $request, ShippingCalculator $shipping, MarketplaceNotificationService $notices)
    {
        $data=$request->validate([
            'customer_name'=>['required','string','max:160'],
            'customer_email'=>['required','email','max:190'],
            'shipping_address'=>['required','string','min:10','max:2000'],
        ]);

        $cart=$request->session()->get('cart',[]);
        if (empty($cart)) {
            return redirect()->route('cart.index')->withErrors(['cart'=>'Cart is empty.']);
        }

        $orderIds=DB::transaction(function () use ($cart,$data,$request,$shipping,$notices) {
            $products=Product::query()->with(['vendor','category'])
                ->whereIn('id',array_keys($cart))->orderBy('id')->lockForUpdate()->get();

            if ($products->count()!==count($cart)) {
                throw ValidationException::withMessages(['cart'=>'A product has been removed. Review your cart.']);
            }
            $groups=[];
            foreach ($products as $product) {
                $quantity=(int)$cart[$product->id];
                if (!$product->isPurchasable() || $quantity<1 || $quantity>$product->stock) {
                    throw ValidationException::withMessages(['cart'=>'A product or quantity is unavailable. Review your cart.']);
                }
                $groups[$product->vendor_id][]=[$product,$quantity];
            }
            $totals=$shipping->breakdown($groups);
            $ids=[];
            foreach ($groups as $vendorId=>$lines) {
                if (!$totals[$vendorId]['enabled']) {
                    throw ValidationException::withMessages(['shipping'=>'The seller is not accepting delivery orders.']);
                }
                $subtotalCents=$totals[$vendorId]['subtotal_cents'];
                $feeCents=$totals[$vendorId]['shipping_cents'];
                $order=Order::create([
                    'number'=>(string)Str::uuid(),
                    'user_id'=>$request->user()->id,
                    'vendor_id'=>$vendorId,
                    'status'=>'pending',
                    'payment_method'=>'cod',
                    'payment_status'=>'unpaid',
                    'customer_name'=>$data['customer_name'],
                    'customer_email'=>$data['customer_email'],
                    'shipping_address'=>$data['shipping_address'],
                    'subtotal'=>$subtotalCents/100,
                    'shipping_fee'=>$feeCents/100,
                    'total'=>($subtotalCents+$feeCents)/100,
                ]);
                foreach ($lines as [$product,$quantity]) {
                    $unitCents=(int)round((float)$product->price*100);
                    $order->items()->create([
                        'product_id'=>$product->id,
                        'title_snapshot'=>$product->title,
                        'unit_price'=>$unitCents/100,
                        'quantity'=>$quantity,
                        'line_total'=>$unitCents*$quantity/100,
                    ]);
                    $product->decrement('stock',$quantity);
                }
                $notices->orderPlaced($order);
                $ids[]=$order->id;
            }
            return $ids;
        },attempts:3);

        $request->session()->forget('cart');
        return redirect()->route('account.orders')
            ->with('success',count($orderIds).' order(s) placed. Pay on delivery.');
    }
}
