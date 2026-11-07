<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;

class StorefrontController extends Controller
{
    public function home()
    {
        $categories = Category::query()->where('is_active', true)->orderBy('name')->get();
        $products = Product::query()->visible()->with(['vendor', 'category'])->latest()->paginate(12);
        return view('storefront.home', compact('categories', 'products'));
    }

    public function catalog(Request $request)
    {
        $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'type' => ['nullable', 'in:physical,digital,service'],
            'category' => ['nullable', 'string', 'max:190'],
        ]);

        $categories = Category::where('is_active', true)->orderBy('name')->get();
        $products = Product::query()->visible()->with(['vendor', 'category'])
            ->when($request->filled('q'), fn ($q) => $q->where('title', 'like', '%'.$request->query('q').'%'))
            ->when($request->filled('type'), fn ($q) => $q->where('type', $request->query('type')))
            ->when($request->filled('category'), fn ($q) => $q->whereHas('category', fn ($categories) => $categories->where('slug', $request->query('category'))))
            ->latest()->paginate(12)->withQueryString();

        return view('storefront.catalog', compact('products', 'categories'));
    }

    public function category(Category $category)
    {
        abort_unless($category->is_active, 404);
        $products = Product::query()->visible()->with(['vendor', 'category'])
            ->where('category_id', $category->id)->latest()->paginate(12);
        return view('storefront.catalog', [
            'products' => $products,
            'categories' => Category::where('is_active', true)->orderBy('name')->get(),
            'selectedCategory' => $category,
        ]);
    }

    public function product(Product $product)
    {
        abort_unless(Product::query()->visible()->whereKey($product->id)->exists(), 404);
        $product->load(['category', 'vendor']);
        return view('storefront.product', compact('product'));
    }
}
