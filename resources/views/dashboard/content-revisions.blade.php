@extends('layouts.workspace')
@section('title','Revision History')
@section('subtitle',$entry->title)
@section('page_actions')<a class="btn btn-primary" href="{{ route('admin.content.index',$type) }}">Back to {{ ucfirst($type) }} Manager</a>@endsection
@section('content')
<div class="table-panel"><div class="table-scroll"><table class="data-table"><thead><tr><th>Revision</th><th>Title</th><th>Status</th><th>Saved</th><th>Action</th></tr></thead><tbody>
@foreach($revisions as $revision)<tr><td>v{{ $revision->revision }}</td><td>{{ $revision->snapshot['title'] ?? $entry->title }}</td><td>{{ $revision->snapshot['status'] ?? 'draft' }}</td><td>{{ $revision->created_at->format('Y-m-d H:i') }}</td>
<td><x-ui.action-trigger menu="rev-menu-{{ $revision->id }}" label="Row actions" /></td></tr>@endforeach
</tbody></table></div>@include('partials.table-footer',['paginator'=>$revisions])</div>
@foreach($revisions as $revision)
<div class="action-popover" popover="auto" id="rev-menu-{{ $revision->id }}"><button type="button" data-open-modal="restore-{{ $revision->id }}">Restore revision</button></div>
<dialog class="modal" id="restore-{{ $revision->id }}"><div class="modal-header"><h2>Restore revision {{ $revision->revision }}?</h2><button class="modal-close" data-close-modal type="button">@include('partials.icon',['name'=>'close'])</button></div>
<form method="POST" action="{{ route('admin.content.restore',['type'=>$type,'entry'=>$entry,'revision'=>$revision->revision]) }}">@csrf
<div class="modal-body">A new revision will be created from version {{ $revision->revision }}. The current revision will remain in the history.</div>
<div class="modal-footer"><button class="btn btn-outline" type="button" data-close-modal>Cancel</button><button class="btn btn-primary" type="submit">Restore Revision</button></div></form></dialog>
@endforeach
@endsection
