<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class CartController extends Controller
{
    public function index(Request $request)
    {
        return view('storefront.cart', $this->summary($request));
    }

    public function add(Request $request, Product $product)
    {
        $data = $request->validate(['quantity' => ['required', 'integer', 'min:1', 'max:99']]);
        $product->load(['vendor', 'category']);

        if (! $product->isPurchasable()) {
            throw ValidationException::withMessages(['quantity' => 'This product is not available for checkout.']);
        }

        $cart = $request->session()->get('cart', []);
        $newQuantity = ((int) ($cart[$product->id] ?? 0)) + $data['quantity'];

        if ($newQuantity > $product->stock) {
            throw ValidationException::withMessages(['quantity' => 'The selected quantity exceeds available stock.']);
        }

        $cart[$product->id] = $newQuantity;
        $request->session()->put('cart', $cart);
        return redirect()->route('cart.index')->with('success', 'Added to cart.');
    }

    public function update(Request $request, Product $product)
    {
        $data = $request->validate(['quantity' => ['required', 'integer', 'min:0', 'max:99']]);
        $cart = $request->session()->get('cart', []);
        abort_unless(array_key_exists($product->id, $cart), 404);

        if ($data['quantity'] > 0) {
            $product->load(['vendor', 'category']);
            if (! $product->isPurchasable() || $data['quantity'] > $product->stock) {
                return back()->withErrors(['quantity' => 'This quantity is unavailable.']);
            }
            $cart[$product->id] = $data['quantity'];
        } else {
            unset($cart[$product->id]);
        }

        $request->session()->put('cart', $cart);
        return back()->with('success', 'Cart updated.');
    }

    public function remove(Request $request, Product $product)
    {
        $cart = $request->session()->get('cart', []);
        unset($cart[$product->id]);
        $request->session()->put('cart', $cart);
        return back()->with('success', 'Product removed.');
    }

    public function summary(Request $request): array
    {
        $cart = $request->session()->get('cart', []);
        $products = Product::with(['vendor', 'category'])->whereIn('id', array_keys($cart))->get();
        $items = collect();
        $cents = 0;

        foreach ($products as $product) {
            $quantity = (int) ($cart[$product->id] ?? 0);
            if ($quantity < 1) {
                continue;
            }
            $lineCents = (int) round(((float) $product->price) * 100) * $quantity;
            $cents += $lineCents;
            $items->push(compact('product', 'quantity', 'lineCents'));
        }

        return ['items' => $items, 'total' => $cents / 100];
    }
}
