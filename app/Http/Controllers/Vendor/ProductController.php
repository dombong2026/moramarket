<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\MediaAsset;
use App\Models\Product;
use App\Services\MediaAssetService;
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
        $products = $vendor->products()->with(['category', 'featuredMedia'])
            ->when($request->filled('q'), fn ($q) => $q->where('title', 'like', '%'.$request->query('q').'%'))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->query('status')))
            ->latest()->paginate(10)->withQueryString();
        $categories = Category::where('is_active', true)->orderBy('name')->get();
        $mediaAssets = MediaAsset::where('user_id', $request->user()->id)->where('mime_type','like','image/%')->latest()->limit(100)->get();

        return view('dashboard.products', compact('products', 'categories', 'mediaAssets'));
    }

    public function store(Request $request, MediaAssetService $service)
    {
        $data = $this->validateProduct($request);
        $data['vendor_id'] = $request->user()->vendor->id;
        $data['slug'] = $this->uniqueSlug($data['title']);

        $this->applyFeaturedImage($data, $request, $service);
        Product::create($data);

        return back()->with('success', 'Product created.');
    }

    public function update(Request $request, Product $product, MediaAssetService $service)
    {
        $this->authorizeOwner($request, $product);
        $data = $this->validateProduct($request);
        $previousPath = $product->image_path;
        $previousAsset = $product->featured_media_id;

        $this->applyFeaturedImage($data, $request, $service);
        $product->update($data);

        if (array_key_exists('image_path', $data) && $previousPath && ! $previousAsset && $previousPath !== $data['image_path']) {
            Storage::disk('public')->delete($previousPath);
        }

        return back()->with('success', 'Product updated.');
    }

    public function destroy(Request $request, Product $product)
    {
        $this->authorizeOwner($request, $product);

        if ($product->orderItems()->exists()) {
            return back()->withErrors(['product' => 'Ordered products cannot be deleted. Move them to draft instead.']);
        }

        $path = $product->image_path;
        $managedImage = (bool) $product->featured_media_id;
        $product->delete();

        if ($path && ! $managedImage) {
            Storage::disk('public')->delete($path);
        }

        return back()->with('success', 'Product deleted.');
    }

    private function applyFeaturedImage(array &$data, Request $request, MediaAssetService $service): void
    {
        if ($request->hasFile('image')) {
            $asset = $service->store($request->file('image'), $request->user(), (string) $data['title']);
            $data['featured_media_id'] = $asset->id;
            $data['image_path'] = $asset->path;
        } elseif (! empty($data['media_id'])) {
            $asset = MediaAsset::where('user_id', $request->user()->id)->where('mime_type','like','image/%')->findOrFail($data['media_id']);
            $data['featured_media_id'] = $asset->id;
            $data['image_path'] = $asset->path;
        } elseif ($request->boolean('remove_image')) {
            $data['featured_media_id'] = null;
            $data['image_path'] = null;
        }

        unset($data['image'], $data['media_id'], $data['remove_image']);
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
            'media_id' => ['nullable', 'integer', Rule::exists('media_assets', 'id')->where(fn ($query) => $query->where('user_id', $request->user()->id)->where('mime_type', 'like', 'image/%'))],
            'remove_image' => ['nullable', 'boolean'],
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
