<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', 'in:draft,published'],
        ]);
        $vendor = $request->user()->vendor;
        $products = $vendor->products()->with('category')
            ->when($request->filled('q'), fn ($q) => $q->where('title', 'like', '%'.$request->query('q').'%'))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->query('status')))
            ->latest()->paginate(10)->withQueryString();
        $categories = Category::where('is_active', true)->orderBy('name')->get();
        return view('dashboard.products', compact('products', 'categories'));
    }

    public function store(Request $request)
    {
        $data = $this->validateProduct($request);
        $data['vendor_id'] = $request->user()->vendor->id;
        $data['slug'] = $this->uniqueSlug($data['title']);
        if ($request->hasFile('image')) {
            $data['image_path'] = $request->file('image')->store('products', 'public');
        }
        Product::create($data);
        return back()->with('success', 'Product created.');
    }

    public function update(Request $request, Product $product)
    {
        $this->authorizeOwner($request, $product);
        $data = $this->validateProduct($request);
        if ($request->hasFile('image')) {
            $data['image_path'] = $request->file('image')->store('products', 'public');
        }

        $oldImage = $product->image_path;
        $product->update($data);
        if (isset($data['image_path']) && $oldImage) {
            Storage::disk('public')->delete($oldImage);
        }
        return back()->with('success', 'Product updated.');
    }

    public function destroy(Request $request, Product $product)
    {
        $this->authorizeOwner($request, $product);
        if ($product->orderItems()->exists()) {
            return back()->withErrors(['product' => 'Ordered products cannot be deleted. Move them to draft instead.']);
        }
        $image = $product->image_path;
        $product->delete();
        if ($image) {
            Storage::disk('public')->delete($image);
        }
        return back()->with('success', 'Product deleted.');
    }

    private function validateProduct(Request $request): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'min:3', 'max:190'],
            'category_id' => ['required', Rule::exists('categories', 'id')->where('is_active', true)],
            'type' => ['required', 'in:physical,digital,service'],
            'description' => ['nullable', 'string', 'max:20000'],
            'price' => ['required', 'numeric', 'min:0', 'max:9999999.99', 'decimal:0,2'],
            'stock' => ['required', 'integer', 'min:0', 'max:100000000'],
            'status' => ['required', 'in:draft,published'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ]);
    }

    private function authorizeOwner(Request $request, Product $product): void
    {
        abort_unless($product->vendor_id === $request->user()->vendor->id, 404);
    }

    private function uniqueSlug(string $title): string
    {
        $slug = Str::slug($title) ?: 'product';
        return $slug.'-'.Str::lower(Str::random(6));
    }
}
