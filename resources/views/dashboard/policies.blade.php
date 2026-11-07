@extends('layouts.workspace')
@section('title','Policy Center')
@section('subtitle','Draft, review, publish and update your marketplace policies. Legal review remains your responsibility.')
@section('page_actions')<button class="btn btn-primary" type="button" data-open-modal="policy-create">@include('partials.icon',['name'=>'plus']) Add Policy</button>@endsection
@section('content')
<div class="table-panel">@include('partials.table-tools',['statuses'=>['draft'=>'Draft','published'=>'Published']])
<div class="table-scroll"><table class="data-table"><thead><tr><th>No</th><th>Title</th><th>URL Slug</th><th>Status</th><th>Version</th><th>Updated</th><th>Action</th></tr></thead><tbody>
@forelse($policies as $policy)<tr><td>{{ $policies->firstItem()+$loop->index }}</td><td><strong>{{ $policy->title }}</strong></td><td>{{ $policy->slug }}</td><td><span class="pill {{ $policy->status }}">{{ $policy->status }}</span></td><td>{{ $policy->revision }}</td><td>{{ $policy->updated_at->format('Y-m-d') }}</td><td><x-ui.action-trigger menu="policy-menu-{{ $policy->id }}" label="Row actions" /></td></tr>@empty<tr><td colspan="7" class="muted" style="padding:26px;text-align:center">No policies found.</td></tr>@endforelse
</tbody></table></div>@include('partials.table-footer',['paginator'=>$policies])</div>
<x-ui.modal-frame id="policy-create" size="large"><div class="modal-header"><h2>Add Policy</h2><button class="modal-close" type="button" data-close-modal>@include('partials.icon',['name'=>'close'])</button></div>
<form method="POST" action="{{ route('admin.policies.store') }}">@csrf<div class="modal-body">
<div class="field-block"><label class="field-label" for="policy-title-create">Policy title</label><input class="field" id="policy-title-create" name="title" maxlength="190" required></div>
@include('partials.rich-editor',['id'=>'policy-create-editor','label'=>'Policy content','html'=>''])
<div class="field-block"><label class="field-label" for="policy-status-create">Status</label><select class="field" id="policy-status-create" name="status"><option value="draft">Draft</option><option value="published">Published</option></select></div>
<p class="native-note">Publishing makes this legal policy visible immediately. Review it for accuracy and local requirements first.</p>
</div><div class="modal-footer"><button class="btn btn-outline" type="button" data-close-modal>Cancel</button><button class="btn btn-primary" type="submit">Create Policy</button></div></form></x-ui.modal-frame>
@foreach($policies as $policy)
<x-ui.action-menu id="policy-menu-{{ $policy->id }}">@if($policy->status==='published')<a href="{{ route('policies.show',$policy->slug) }}">View live policy</a>@endif<button type="button" data-open-modal="policy-edit-{{ $policy->id }}">Edit</button><button class="danger" type="button" data-open-modal="policy-delete-{{ $policy->id }}">Delete</button></x-ui.action-menu>
<x-ui.modal-frame id="policy-edit-{{ $policy->id }}" size="large"><div class="modal-header"><h2>Edit Policy</h2><button class="modal-close" type="button" data-close-modal>@include('partials.icon',['name'=>'close'])</button></div>
<form method="POST" action="{{ route('admin.policies.update',$policy) }}">@csrf @method('PATCH')
<div class="modal-body"><div class="field-block"><label class="field-label" for="policy-name-{{ $policy->id }}">Title</label><input class="field" name="title" id="policy-name-{{ $policy->id }}" value="{{ $policy->title }}" maxlength="190" required></div>
@include('partials.rich-editor',['id'=>'policy-editor-'.$policy->id,'label'=>'Policy content','html'=>$policy->body_html])
<div class="field-block"><label for="policy-status-{{ $policy->id }}" class="field-label">Status</label><select class="field" name="status" id="policy-status-{{ $policy->id }}"><option value="draft" @selected($policy->status==='draft')>Draft</option><option value="published" @selected($policy->status==='published')>Published</option></select></div>
<p class="native-note">Revision {{ $policy->revision }} · Previous versions are retained for auditing, but restoration is not yet available.</p>
</div><div class="modal-footer"><button class="btn btn-outline" type="button" data-close-modal>Cancel</button><button class="btn btn-primary" type="submit">Save Changes</button></div></form></x-ui.modal-frame>
<x-ui.modal-frame id="policy-delete-{{ $policy->id }}"><div class="modal-header"><h2>Delete Policy?</h2><button class="modal-close" type="button" data-close-modal>@include('partials.icon',['name'=>'close'])</button></div><form method="POST" action="{{ route('admin.policies.delete',$policy) }}">@csrf @method('DELETE')
<div class="modal-body">Delete <strong>{{ $policy->title }}</strong> and its revision history? This cannot be undone.</div>
<div class="modal-footer"><button class="btn btn-outline" type="button" data-close-modal>Cancel</button><button class="btn btn-danger" type="submit">Delete Policy</button></div></form></x-ui.modal-frame>
@endforeach
@endsection
