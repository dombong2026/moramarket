@extends('layouts.workspace')
@section('title',$type==='page'?'Page Manager':'Post Manager')
@section('subtitle','Create, publish and revise '.($type==='page'?'public pages.':'blog posts.'))
@section('page_actions')
<button class="btn btn-primary" type="button" data-open-modal="content-create">@include('partials.icon',['name'=>'plus']) Add {{ ucfirst($type) }}</button>
@endsection
@section('content')
<div class="table-panel">
@include('partials.table-tools',['statuses'=>['draft'=>'Draft','published'=>'Published']])
<div class="table-scroll"><table class="data-table"><thead><tr><th>No</th><th>Title</th><th>Author</th><th>Status</th><th>Revision</th><th>Updated</th><th>Action</th></tr></thead><tbody>
@forelse($entries as $entry)
<tr><td>{{ $entries->firstItem()+$loop->index }}</td><td><strong>{{ $entry->title }}</strong><small class="small">{{ $entry->slug }}</small></td><td>{{ $entry->author->name }}</td>
<td><span class="pill {{ $entry->status }}">{{ ucfirst($entry->status) }}</span></td><td>{{ $entry->revision }}</td>
<td>{{ $entry->updated_at->format('Y-m-d') }}</td><td><button class="action-trigger" type="button" data-menu-id="entry-menu-{{ $entry->id }}" aria-label="Actions">⋮</button></td></tr>
@empty<tr><td colspan="7" class="muted" style="padding:28px;text-align:center">No matching {{ $type }}s.</td></tr>@endforelse
</tbody></table></div>@include('partials.table-footer',['paginator'=>$entries])</div>
<dialog class="modal modal-lg" id="content-create"><div class="modal-header"><h2>Create {{ ucfirst($type) }}</h2><button type="button" class="modal-close" data-close-modal>@include('partials.icon',['name'=>'close'])</button></div>
<form method="POST" action="{{ route('admin.content.store',$type) }}">@csrf
<div class="modal-body">
<div class="field-block"><label class="field-label" for="entry-create-title">Title</label><input class="field" name="title" id="entry-create-title" required maxlength="190"></div>
<div class="field-block"><label class="field-label" for="entry-create-excerpt">Summary (optional)</label><textarea class="field" rows="2" name="excerpt" maxlength="400" id="entry-create-excerpt"></textarea></div>
@include('partials.rich-editor',['id'=>'entry-create','label'=>'Page / post content','html'=>''])
<h3 class="form-subheading">Featured media</h3>@include('partials.content-featured-fields',['id'=>'create','entry'=>null])
<h3 class="form-subheading">SEO</h3>
<div class="field-block"><label class="field-label" for="entry-create-seo-title">SEO title</label><input class="field" id="entry-create-seo-title" name="seo_title" maxlength="190"></div>
<div class="field-block"><label class="field-label" for="entry-create-seo-desc">Meta description</label><textarea class="field" rows="2" id="entry-create-seo-desc" name="seo_description" maxlength="300"></textarea></div>
<label class="field-label" for="entry-create-status">Status</label><select class="field" id="entry-create-status" name="status"><option value="draft">Draft</option><option value="published">Published</option></select>
</div><div class="modal-footer"><button class="btn btn-outline" type="button" data-close-modal>Cancel</button><button type="submit" class="btn btn-primary">Save {{ ucfirst($type) }}</button></div></form></dialog>
@foreach($entries as $entry)
<div id="entry-menu-{{ $entry->id }}" class="action-popover" popover="auto">
@if($entry->status==='published')<a href="{{ route('content.show',['locale'=>app()->getLocale(),'type'=>$type,'entry'=>$entry->slug]) }}">View live {{ $type }}</a>@endif
<button type="button" data-open-modal="entry-edit-{{ $entry->id }}">Edit</button>
<a href="{{ route('admin.content.revisions',['type'=>$type,'entry'=>$entry]) }}">Revision history</a>
<button type="button" class="danger" data-open-modal="entry-delete-{{ $entry->id }}">Delete</button></div>
<dialog class="modal modal-lg" id="entry-edit-{{ $entry->id }}"><div class="modal-header"><h2>Edit {{ ucfirst($type) }}</h2><button type="button" class="modal-close" data-close-modal>@include('partials.icon',['name'=>'close'])</button></div>
<form method="POST" action="{{ route('admin.content.update',['type'=>$type,'entry'=>$entry]) }}">@csrf @method('PATCH')
<div class="modal-body">
<div class="field-block"><label class="field-label" for="entry-title-{{ $entry->id }}">Title</label><input class="field" id="entry-title-{{ $entry->id }}" name="title" value="{{ $entry->title }}" maxlength="190" required></div>
<div class="field-block"><label class="field-label" for="entry-excerpt-{{ $entry->id }}">Summary</label><textarea class="field" id="entry-excerpt-{{ $entry->id }}" rows="2" name="excerpt" maxlength="400">{{ $entry->excerpt }}</textarea></div>
@include('partials.rich-editor',['id'=>'entry-'.$entry->id,'label'=>'Page / post content','html'=>$entry->body_html])
<h3 class="form-subheading">Featured media</h3>@include('partials.content-featured-fields',['id'=>'featured-'.$entry->id,'entry'=>$entry])
<h3 class="form-subheading">SEO</h3>
<div class="field-block"><label class="field-label" for="seo-title-{{ $entry->id }}">SEO title</label><input class="field" name="seo_title" id="seo-title-{{ $entry->id }}" value="{{ $entry->seo_title }}" maxlength="190"></div>
<div class="field-block"><label class="field-label" for="seo-desc-{{ $entry->id }}">Meta description</label><textarea class="field" id="seo-desc-{{ $entry->id }}" name="seo_description" maxlength="300" rows="2">{{ $entry->seo_description }}</textarea></div>
<label class="field-label" for="entry-status-{{ $entry->id }}">Status</label><select class="field" id="entry-status-{{ $entry->id }}" name="status"><option value="draft" @selected($entry->status==='draft')>Draft</option><option value="published" @selected($entry->status==='published')>Published</option></select>
</div><div class="modal-footer"><button class="btn btn-outline" type="button" data-close-modal>Cancel</button><button type="submit" class="btn btn-primary">Save Changes</button></div></form></dialog>
<dialog class="modal" id="entry-delete-{{ $entry->id }}"><div class="modal-header"><h2>Delete {{ ucfirst($type) }}?</h2><button class="modal-close" type="button" data-close-modal>@include('partials.icon',['name'=>'close'])</button></div>
<form method="POST" action="{{ route('admin.content.delete',['type'=>$type,'entry'=>$entry]) }}">@csrf @method('DELETE')
<div class="modal-body"><p>Delete <strong>{{ $entry->title }}</strong> and all its revisions? This cannot be undone.</p></div>
<div class="modal-footer"><button class="btn btn-outline" type="button" data-close-modal>Cancel</button><button class="btn btn-danger" type="submit">Delete</button></div></form></dialog>
@endforeach
@endsection
