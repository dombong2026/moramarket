<?php

namespace App\Http\Controllers;

use App\Models\ContentEntry;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ContentPublicController extends Controller
{
    public function pages(string $locale)
    {
        $entries=ContentEntry::query()->public()->where('type','page')->orderBy('title')->paginate(10);
        return view('storefront.content-index',['entries'=>$entries,'type'=>'page']);
    }

    public function blog(string $locale)
    {
        $entries=ContentEntry::query()->public()->where('type','post')
            ->latest('published_at')->paginate(10);
        return view('storefront.content-index',['entries'=>$entries,'type'=>'post']);
    }

    public function page(string $locale, ContentEntry $entry)
    {
        return $this->showEntry($entry,'page');
    }

    public function post(string $locale, ContentEntry $entry)
    {
        return $this->showEntry($entry,'post');
    }

    public function show(string $locale, string $type, ContentEntry $entry)
    {
        return $this->showEntry($entry,$type);
    }

    private function showEntry(ContentEntry $entry,string $type)
    {
        abort_unless($entry->type===$type &&
            ContentEntry::query()->public()->whereKey($entry->id)->exists(),404);
        return view('storefront.content-show',['entry'=>$entry->load('featuredMedia'),'type'=>$type]);
    }

    public function featuredMedia(string $type, ContentEntry $entry): StreamedResponse
    {
        abort_unless(in_array($type,['page','post'],true)
            && $entry->type===$type
            && ContentEntry::query()->public()->whereKey($entry->id)->exists(),404);
        abort_unless(in_array($entry->featured_type,['image','video'],true),404);
        $asset=$entry->featuredMedia;
        abort_unless($asset && str_starts_with($asset->path,'media/')
            && str_starts_with($asset->mime_type,$entry->featured_type.'/'),404);
        $disk=Storage::disk('local');
        abort_unless($disk->exists($asset->path),404);
        return $disk->response($asset->path,null,[
            'Cache-Control'=>'public,max-age=300',
            'X-Content-Type-Options'=>'nosniff',
            'Content-Security-Policy'=>"default-src 'none'; sandbox",
        ]);
    }
}
