<?php

namespace App\Http\Controllers\Media;

use App\Support\TableQuery;

use App\Http\Controllers\Controller;
use App\Models\ContentEntry;
use App\Models\MediaAsset;
use App\Services\MediaAssetService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MediaLibraryController extends Controller
{
    private function isAdministrator(Request $request): bool
    {
        return $request->user()->isAdmin();
    }

    private function scopedAsset(Request $request, MediaAsset $asset): MediaAsset
    {
        abort_unless($this->isAdministrator($request) || $asset->user_id===$request->user()->id,404);
        return $asset;
    }

    public function index(Request $request)
    {
        $query=MediaAsset::query()->with('user')->withCount('products');
        if (!$this->isAdministrator($request)) {
            $query->where('user_id',$request->user()->id);
        }
        $query=TableQuery::apply($query,$request,['original_name','alt_text'],[
            'jpeg'=>'image/jpeg','png'=>'image/png','webp'=>'image/webp',
            'mp4'=>'video/mp4','webm'=>'video/webm',
        ],'mime_type');
        $assets=TableQuery::paginate($query->latest());
        return view('dashboard.media',[
            'assets'=>$assets,
            'workspace'=>$this->isAdministrator($request)?'admin':'vendor',
        ]);
    }

    public function store(Request $request, MediaAssetService $service)
    {
        $data=$request->validate([
            'file'=>['required','file','mimetypes:image/jpeg,image/png,image/webp,video/mp4,video/webm','max:30720'],
            'alt_text'=>['nullable','string','max:160'],
        ]);
        $file=$data['file'];
        if (str_starts_with((string)$file->getMimeType(),'image/') && $file->getSize()>5*1024*1024) {
            throw ValidationException::withMessages(['file'=>'Images must not exceed 5 MB.']);
        }
        $service->store($file,$request->user(),$data['alt_text']??null);
        return back()->with('success','Media added to your Media Library.');
    }

    public function update(Request $request, MediaAsset $asset)
    {
        $this->scopedAsset($request,$asset);
        $data=$request->validate([
            'original_name'=>['required','string','min:1','max:190'],
            'alt_text'=>['nullable','string','max:160'],
        ]);
        $asset->update($data);
        return back()->with('success','Media details updated.');
    }

    public function destroy(Request $request, MediaAsset $asset)
    {
        $this->scopedAsset($request,$asset);
        if ($asset->products()->exists() || ContentEntry::where('featured_media_id',$asset->id)->exists()) {
            throw ValidationException::withMessages(['file'=>'This file is in use. Remove the featured media assignment first.']);
        }
        $path=$asset->path;
        $asset->delete();
        Storage::disk('local')->delete($path);
        return back()->with('success','Media file deleted.');
    }

    public function preview(Request $request, MediaAsset $asset): StreamedResponse
    {
        $this->scopedAsset($request,$asset);
        abort_unless(str_starts_with($asset->path,'media/')
            && Storage::disk('local')->exists($asset->path),404);
        return Storage::disk('local')->response($asset->path,null,[
            'Cache-Control'=>'private,no-store',
            'X-Content-Type-Options'=>'nosniff',
            'Content-Security-Policy'=>"default-src 'none'; sandbox",
        ]);
    }
}
