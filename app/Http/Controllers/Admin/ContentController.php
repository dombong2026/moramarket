<?php

namespace App\Http\Controllers\Admin;

use App\Support\TableQuery;

use App\Http\Controllers\Controller;
use App\Models\ContentEntry;
use App\Models\MediaAsset;
use App\Services\SafeHtml;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ContentController extends Controller
{
    private function type(string $type): string
    {
        abort_unless(in_array($type, ['page', 'post'], true), 404);
        return $type;
    }

    private function ownedEntry(string $type, ContentEntry $entry): ContentEntry
    {
        abort_unless($entry->type === $type, 404);
        return $entry;
    }

    public function index(Request $request, string $type)
    {
        $this->type($type);
        $query=TableQuery::apply(ContentEntry::query()->where('type',$type)->with('author'),$request,['title'],['draft'=>'draft','published'=>'published']);
        $entries=TableQuery::paginate($query->latest());
        $mediaAssets=MediaAsset::where('user_id',$request->user()->id)->latest()->limit(100)->get();
        return view('dashboard.content',compact('type','entries','mediaAssets'));
    }

    public function store(Request $request, string $type, SafeHtml $sanitizer)
    {
        $this->type($type);
        $data = $this->data($request, $sanitizer);
        $data['type'] = $type;
        $data['author_id'] = $request->user()->id;
        $data['slug'] = $this->uniqueSlug($type, $data['title']);
        $data['revision'] = 1;
        $data['published_at'] = $data['status'] === 'published' ? now() : null;

        DB::transaction(function () use ($data, $request) {
            $entry = ContentEntry::create($data);
            $entry->revisions()->create([
                'editor_id'=>$request->user()->id, 'revision'=>1,
                'snapshot'=>$this->snapshot($entry),
            ]);
        });
        return back()->with('success', ucfirst($type).' created.');
    }

    public function update(Request $request, string $type, ContentEntry $entry, SafeHtml $sanitizer)
    {
        $this->ownedEntry($this->type($type), $entry);
        $data = $this->data($request, $sanitizer);

        DB::transaction(function () use ($entry, $data, $request) {
            $locked = ContentEntry::whereKey($entry->id)->lockForUpdate()->firstOrFail();
            $data['revision'] = $locked->revision + 1;
            $data['published_at'] = $data['status'] === 'published' ? ($locked->published_at ?? now()) : null;
            $locked->update($data);
            $locked->revisions()->create([
                'editor_id'=>$request->user()->id,
                'revision'=>$locked->revision, 'snapshot'=>$this->snapshot($locked),
            ]);
        });

        return back()->with('success', ucfirst($type).' updated.');
    }

    public function destroy(string $type, ContentEntry $entry)
    {
        $this->ownedEntry($this->type($type), $entry)->delete();
        return back()->with('success', ucfirst($type).' deleted.');
    }

    public function revisions(string $type, ContentEntry $entry)
    {
        $entry = $this->ownedEntry($this->type($type), $entry);
        return view('dashboard.content-revisions', [
            'entry'=>$entry,
            'type'=>$type,
            'revisions'=>$entry->revisions()->latest('revision')->paginate(10),
        ]);
    }

    public function restore(Request $request, string $type, ContentEntry $entry, int $revision)
    {
        $this->ownedEntry($this->type($type), $entry);
        DB::transaction(function () use ($request, $entry, $revision) {
            $locked = ContentEntry::whereKey($entry->id)->lockForUpdate()->firstOrFail();
            $source = $locked->revisions()->where('revision',$revision)->firstOrFail();
            $data = $source->snapshot;
            // Restore does not change type, immutable URL slug or owner.
            unset($data['type'], $data['slug'], $data['author_id'], $data['revision'], $data['published_at']);
            if (($data['status'] ?? '') === 'published') {
                $data['published_at'] = now();
            } else {
                $data['published_at'] = null;
            }
            $data['revision'] = $locked->revision + 1;
            $locked->update($data);
            $locked->revisions()->create([
                'editor_id'=>$request->user()->id, 'revision'=>$locked->revision,
                'snapshot'=>$this->snapshot($locked),
            ]);
        });
        return redirect()->route('admin.content.revisions', ['type'=>$type,'entry'=>$entry])
            ->with('success','A new revision was created from the selected version.');
    }

    private function data(Request $request, SafeHtml $sanitizer): array
    {
        $data = $request->validate([
            'title'=>['required','string','min:3','max:190'],
            'excerpt'=>['nullable','string','max:400'],
            'body_html'=>['required','string','max:100000'],
            'status'=>['required',Rule::in(['draft','published'])],
            'seo_title'=>['nullable','string','max:190'],
            'seo_description'=>['nullable','string','max:300'],
            'featured_type'=>['required',Rule::in(['none','image','video','youtube','color'])],
            'featured_media_id'=>['nullable','integer',Rule::exists('media_assets','id')->where('user_id',$request->user()->id)],
            'featured_youtube_id'=>['nullable','string','max:250'],
            'featured_color'=>['nullable','string','regex:/^#[A-Fa-f0-9]{6}$/'],
        ]);

        $data['body_html'] = $sanitizer->clean($data['body_html']);
        if (trim(strip_tags($data['body_html'])) === '') {
            throw ValidationException::withMessages(['body_html'=>'Content must contain readable text.']);
        }

        $kind = $data['featured_type'];
        if (in_array($kind, ['image','video'], true)) {
            $asset = MediaAsset::where('user_id',$request->user()->id)->find($data['featured_media_id'] ?? null);
            if (! $asset || ! str_starts_with($asset->mime_type, $kind === 'image' ? 'image/' : 'video/')) {
                throw ValidationException::withMessages(['featured_media_id'=>'Select an uploaded '.$kind.' from your Media Library.']);
            }
        } else {
            $data['featured_media_id'] = null;
        }

        if ($kind === 'youtube') {
            $id = $this->youtubeId((string) ($data['featured_youtube_id'] ?? ''));
            if (! $id) {
                throw ValidationException::withMessages(['featured_youtube_id'=>'Enter a valid YouTube video URL or ID.']);
            }
            $data['featured_youtube_id'] = $id;
        } else {
            $data['featured_youtube_id'] = null;
        }

        if ($kind === 'color' && empty($data['featured_color'])) {
            throw ValidationException::withMessages(['featured_color'=>'Select a valid color.']);
        }
        if ($kind !== 'color') {
            $data['featured_color'] = null;
        }
        return $data;
    }

    private function youtubeId(string $input): ?string
    {
        $input = trim($input);
        if (preg_match('/^[a-zA-Z0-9_-]{11}$/D', $input)) {
            return $input;
        }
        $parts = parse_url($input);
        if (! is_array($parts) || ($parts['scheme'] ?? '') !== 'https') {
            return null;
        }
        $host = strtolower((string) ($parts['host'] ?? ''));
        $path = trim((string) ($parts['path'] ?? ''), '/');
        if ($host === 'youtu.be') {
            $id = $path;
        } elseif (in_array($host, ['youtube.com','www.youtube.com','m.youtube.com','www.youtube-nocookie.com'], true)) {
            if ($path === 'watch') {
                parse_str((string) ($parts['query'] ?? ''), $query);
                $id = (string) ($query['v'] ?? '');
            } elseif (str_starts_with($path, 'embed/') || str_starts_with($path, 'shorts/')) {
                $id = substr($path, strpos($path, '/') + 1);
            } else {
                return null;
            }
        } else {
            return null;
        }
        return preg_match('/^[a-zA-Z0-9_-]{11}$/D', $id) ? $id : null;
    }

    private function snapshot(ContentEntry $entry): array
    {
        return $entry->only([
            'type','author_id','slug','title','excerpt','body_html','status',
            'seo_title','seo_description','featured_type','featured_media_id',
            'featured_youtube_id','featured_color','published_at',
        ]);
    }

    private function uniqueSlug(string $type, string $title): string
    {
        $base = Str::slug($title) ?: 'entry';
        $slug = substr($base, 0, 175);
        while (ContentEntry::where('type',$type)->where('slug',$slug)->exists()) {
            $slug = substr($base, 0, 163).'-'.Str::lower(Str::random(8));
        }
        return $slug;
    }
}
