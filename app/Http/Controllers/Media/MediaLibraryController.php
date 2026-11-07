<?php

namespace App\Http\Controllers\Media;

use App\Http\Controllers\Controller;
use App\Models\MediaAsset;
use App\Services\MediaAssetService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MediaLibraryController extends Controller
{
    private function isAdministrator(Request $request): bool
    {
        return $request->user()->isAdmin();
    }

    private function scopedAsset(Request $request, MediaAsset $asset): MediaAsset
    {
        abort_unless($this->isAdministrator($request) || $asset->user_id === $request->user()->id, 404);
        return $asset;
    }

    public function index(Request $request)
    {
        $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', 'in:jpeg,png,webp'],
        ]);

        $query = MediaAsset::query()->with('user')->withCount('products');
        if (! $this->isAdministrator($request)) {
            $query->where('user_id', $request->user()->id);
        }
        $assets = $query
            ->when($request->filled('q'), fn ($q) => $q->where(function ($sub) use ($request) {
                $text = '%'.$request->query('q').'%';
                $sub->where('original_name', 'like', $text)->orWhere('alt_text', 'like', $text);
            }))
            ->when($request->filled('status'), fn ($q) => $q->where('mime_type', 'image/'.$request->query('status')))
            ->latest()->paginate(10)->withQueryString();

        return view('dashboard.media', [
            'assets' => $assets,
            'workspace' => $this->isAdministrator($request) ? 'admin' : 'vendor',
        ]);
    }

    public function store(Request $request, MediaAssetService $service)
    {
        $data = $request->validate([
            'file' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'alt_text' => ['nullable', 'string', 'max:160'],
        ]);

        $service->store($data['file'], $request->user(), $data['alt_text'] ?? null);

        return back()->with('success', 'Image uploaded to the Media Library.');
    }

    public function update(Request $request, MediaAsset $asset)
    {
        $this->scopedAsset($request, $asset);
        $data = $request->validate([
            'original_name' => ['required', 'string', 'min:1', 'max:190'],
            'alt_text' => ['nullable', 'string', 'max:160'],
        ]);

        $asset->update($data);
        return back()->with('success', 'Media details updated.');
    }

    public function destroy(Request $request, MediaAsset $asset)
    {
        $this->scopedAsset($request, $asset);
        if ($asset->products()->exists()) {
            return back()->withErrors(['file' => 'This image is in use by a product. Choose another product image before deleting.']);
        }

        $path = $asset->path;
        $asset->delete();
        Storage::disk('local')->delete($path);
        return back()->with('success', 'Image deleted.');
    }

    public function preview(Request $request, MediaAsset $asset): StreamedResponse
    {
        $this->scopedAsset($request, $asset);
        abort_unless(str_starts_with($asset->path, 'media/') && Storage::disk('local')->exists($asset->path), 404);

        return Storage::disk('local')->response($asset->path, null, [
            'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
