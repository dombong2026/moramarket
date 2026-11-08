<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;

class WishlistController extends Controller
{
    public function index(Request $request)
    {
        $products = $request->user()->wishlistProducts()
            ->with(['vendor', 'category'])
            ->orderByPivot('created_at', 'desc')
            ->paginate(12);

        return view('dashboard.wishlist', compact('products'));
    }

    public function store(Request $request, Product $product)
    {
        abort_unless(Product::query()->visible()->whereKey($product->id)->exists(), 404);

        $request->user()->wishlistProducts()->syncWithoutDetaching([$product->id]);

        return back()->with('success', 'Product saved to your wishlist.');
    }

    public function destroy(Request $request, Product $product)
    {
        $request->user()->wishlistProducts()->detach($product->id);

        return back()->with('success', 'Product removed from your wishlist.');
    }
}
