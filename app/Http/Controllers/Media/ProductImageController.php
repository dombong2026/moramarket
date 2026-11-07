<?php

namespace App\Http\Controllers\Media;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProductImageController extends Controller
{
    public function show(Request $request, Product $product): StreamedResponse
    {
        $publicListing = Product::query()->visible()->whereKey($product->id)->exists();
        $user = $request->user();
        $owner = $user?->isApprovedVendor() && $product->vendor_id === $user->vendor?->id;
        $admin = $user?->isAdmin() ?? false;

        abort_unless($publicListing || $owner || $admin, 404);

        $path = $product->image_path;
        abort_unless(is_string($path) && str_starts_with($path, 'products/'), 404);

        $disk = Storage::disk('public');
        abort_unless($disk->exists($path), 404);

        // Serve from the Laravel disk without requiring a symlink or exposing drafts.
        return $disk->response($path, null, [
            'Cache-Control' => 'private, max-age=300',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
