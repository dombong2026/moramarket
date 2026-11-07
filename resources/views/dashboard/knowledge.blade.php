@extends('layouts.workspace')
@section('title','Knowledge Base')
@section('subtitle','Manage searchable help articles and their categories.')
@section('page_actions')
<button type="button" class="btn btn-outline" data-open-modal="kb-categories">Categories</button>
<button class="btn btn-primary" type="button" data-open-modal="kb-create">@include('partials.icon',['name'=>'plus']) New Article</button>
@endsection
@section('content')
<div class="table-panel">@include('partials.table-tools',['statuses'=>['draft'=>'Draft','published'=>'Published']])
<div class="table-scroll"><table class="data-table"><thead><tr><th>No</th><th>Title</th><th>Category</th><th>Status</th><th>Updated</th><th>Action</th></tr></thead><tbody>
@forelse($articles as $article)<tr><td>{{ $articles->firstItem()+$loop->index }}</td><td><strong>{{ $article->title }}</strong></td><td>{{ $article->category->name }}</td><td><span class="pill {{ $article->status }}">{{ $article->status }}</span></td><td>{{ $article->updated_at->format('Y-m-d') }}</td><td><x-ui.action-trigger menu="kb-menu-{{ $article->id }}" label="Row actions" /></td></tr>@empty<tr><td colspan="6" class="muted" style="text-align:center;padding:28px">No help articles match your search.</td></tr>@endforelse
</tbody></table></div>@include('partials.table-footer',['paginator'=>$articles])</div>
<dialog class="modal modal-lg" id="kb-categories"><div class="modal-header"><h2>Help Categories</h2><button class="modal-close" type="button" data-close-modal>@include('partials.icon',['name'=>'close'])</button></div>
<div class="modal-body">
<form method="POST" action="{{ route('admin.knowledge.category.store') }}" style="display:flex;gap:9px;align-items:end;flex-wrap:wrap">@csrf
<div style="flex:1"><label class="field-label" for="kb-cat-name">New category name</label><input class="field" name="name" id="kb-cat-name" maxlength="160" required></div><button class="btn btn-primary" type="submit">Add Category</button></form>
<div style="margin-top:19px">
@forelse($categories as $category)
<div class="media-category-row">
<form method="POST" action="{{ route('admin.knowledge.category.update',$category) }}" style="display:flex;gap:7px;flex:1;align-items:center">@csrf @method('PATCH')
<label class="sr-only" for="kb-name-{{ $category->id }}">Category name</label><input class="field" name="name" id="kb-name-{{ $category->id }}" value="{{ $category->name }}" required maxlength="160"><span class="pill">{{ $category->articles_count }} articles</span><button type="submit" class="btn btn-outline btn-sm">Save</button></form>
<form method="POST" action="{{ route('admin.knowledge.category.delete',$category) }}" data-confirm="Delete this category? All articles must be moved or deleted first.">@csrf @method('DELETE')
<button class="btn btn-danger btn-sm" type="submit" @disabled($category->articles_count>0)>Delete</button></form></div>
@empty<p class="muted">Create a category before publishing articles.</p>@endforelse
</div></div><div class="modal-footer"><button class="btn btn-outline" type="button" data-close-modal>Close</button></div></dialog>
<dialog class="modal modal-lg" id="kb-create"><div class="modal-header"><h2>Create Help Article</h2><button class="modal-close" type="button" data-close-modal>@include('partials.icon',['name'=>'close'])</button></div>
<form method="POST" action="{{ route('admin.knowledge.store') }}">@csrf<div class="modal-body">
<div class="field-block"><label class="field-label" for="kb-create-title">Article title</label><input class="field" name="title" id="kb-create-title" required maxlength="190"></div>
<div class="field-block"><label class="field-label" for="kb-create-category">Category</label><select class="field" id="kb-create-category" name="category_id" required><option value="">Choose category</option>@foreach($categories as $category)<option value="{{ $category->id }}">{{ $category->name }}</option>@endforeach</select></div>
@include('partials.rich-editor',['id'=>'kb-create-editor','label'=>'Article content','html'=>''])
<div class="field-block"><label class="field-label" for="kb-create-status">Status</label><select class="field" name="status" id="kb-create-status"><option value="draft">Draft</option><option value="published">Published</option></select></div>
</div><div class="modal-footer"><button class="btn btn-outline" type="button" data-close-modal>Cancel</button><button class="btn btn-primary" type="submit">Create Article</button></div></form></dialog>
@foreach($articles as $article)
<div class="action-popover" id="kb-menu-{{ $article->id }}" popover="auto">@if($article->status==='published')<a href="{{ route('knowledge.show',$article->slug) }}">View published article</a>@endif<button type="button" data-open-modal="kb-edit-{{ $article->id }}">Edit</button><button class="danger" type="button" data-open-modal="kb-delete-{{ $article->id }}">Delete</button></div>
<dialog class="modal modal-lg" id="kb-edit-{{ $article->id }}"><div class="modal-header"><h2>Edit Help Article</h2><button class="modal-close" type="button" data-close-modal>@include('partials.icon',['name'=>'close'])</button></div><form method="POST" action="{{ route('admin.knowledge.update',$article) }}">@csrf @method('PATCH')
<div class="modal-body"><div class="field-block"><label class="field-label" for="kb-title-{{ $article->id }}">Title</label><input class="field" name="title" id="kb-title-{{ $article->id }}" value="{{ $article->title }}" maxlength="190" required></div>
<div class="field-block"><label class="field-label" for="kb-category-{{ $article->id }}">Category</label><select class="field" name="category_id" id="kb-category-{{ $article->id }}">@foreach($categories as $category)<option value="{{ $category->id }}" @selected($category->id===$article->category_id)>{{ $category->name }}</option>@endforeach</select></div>
@include('partials.rich-editor',['id'=>'kb-editor-'.$article->id,'label'=>'Article content','html'=>$article->body_html])
<div class="field-block"><label class="field-label" for="kb-status-{{ $article->id }}">Status</label><select class="field" name="status" id="kb-status-{{ $article->id }}"><option value="draft" @selected($article->status==='draft')>Draft</option><option value="published" @selected($article->status==='published')>Published</option></select></div>
</div><div class="modal-footer"><button class="btn btn-outline" type="button" data-close-modal>Cancel</button><button class="btn btn-primary" type="submit">Save Article</button></div></form></dialog>
<dialog class="modal" id="kb-delete-{{ $article->id }}"><div class="modal-header"><h2>Delete Help Article?</h2><button class="modal-close" type="button" data-close-modal>@include('partials.icon',['name'=>'close'])</button></div>
<form method="POST" action="{{ route('admin.knowledge.delete',$article) }}">@csrf @method('DELETE')<div class="modal-body">Delete <strong>{{ $article->title }}</strong>? This is permanent.</div><div class="modal-footer"><button class="btn btn-outline" type="button" data-close-modal>Cancel</button><button class="btn btn-danger" type="submit">Delete</button></div></form></dialog>
@endforeach
@endsection
