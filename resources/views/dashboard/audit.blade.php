@extends('layouts.workspace')
@section('title','Security Center')
@section('subtitle','Read-only activity records for marketplace content and commerce changes. Sensitive field values are never stored.')
@section('page_actions')
<a class="btn btn-primary" href="{{ route('admin.audit.index') }}">Refresh Activity</a>
@endsection
@section('content')
<div class="panel panel-pad" style="margin-bottom:20px">
  <h2 style="font-weight:790;font-size:18px">Activity Audit</h2>
  <p class="muted" style="margin-top:6px">Changes to vendors, products, categories, orders, editorial content, policies, support tickets and media are recorded at the model layer. This view is accessible only to administrators.</p>
</div>
<div class="table-panel">
@include('partials.table-tools',['statuses'=>['created'=>'Created','updated'=>'Updated','deleted'=>'Deleted','signed_in'=>'Signed In','signed_out'=>'Signed Out']])
<div class="table-scroll">
<table class="data-table"><thead><tr><th>No</th><th>Action</th><th>Resource</th><th>Resource ID</th><th>Actor</th><th>Timestamp</th><th>Actions</th></tr></thead><tbody>
@forelse($events as $event)
<tr>
<td>{{ $events->firstItem()+$loop->index }}</td>
<td><span class="pill {{ $event->action==='created'?'approved':($event->action==='deleted'?'rejected':'pending') }}">{{ ucfirst($event->action) }}</span></td>
<td>{{ class_basename($event->subject_type) }}</td>
<td>{{ $event->subject_id }}</td>
<td>{{ $event->actor?->name ?? 'System' }}</td>
<td>{{ $event->created_at?->format('Y-m-d H:i') }}</td>
<td><x-ui.action-trigger menu="audit-menu-{{ $event->id }}" label="Audit event actions" /></td>
</tr>
@empty<tr><td colspan="7" class="muted" style="text-align:center;padding:30px">No matching audit events.</td></tr>@endforelse
</tbody></table></div>
@include('partials.table-footer',['paginator'=>$events])
</div>
@foreach($events as $event)
<x-ui.action-menu id="audit-menu-{{ $event->id }}"><button type="button" data-open-modal="audit-view-{{ $event->id }}">View event</button></x-ui.action-menu>
<x-ui.modal-frame id="audit-view-{{ $event->id }}">
<div class="modal-header"><h2>Activity Details</h2><button type="button" class="modal-close" data-close-modal aria-label="Close">@include('partials.icon',['name'=>'close'])</button></div>
<div class="modal-body">
<div class="form-grid">
<div><strong>Action</strong><p>{{ ucfirst($event->action) }}</p></div>
<div><strong>Resource</strong><p>{{ class_basename($event->subject_type) }} #{{ $event->subject_id }}</p></div>
<div><strong>Actor</strong><p>{{ $event->actor?->name ?? 'System' }}</p></div>
<div><strong>Time</strong><p>{{ $event->created_at?->format('Y-m-d H:i') }}</p></div>
</div>
<div style="margin-top:16px"><strong>Changed field names</strong>
<p style="overflow-wrap:anywhere" class="muted">{{ implode(', ', $event->changed_fields ?? []) ?: 'Not applicable' }}</p>
</div>
</div><div class="modal-footer"><button class="btn btn-outline" type="button" data-close-modal>Close</button></div>
</x-ui.modal-frame>
@endforeach
@endsection
