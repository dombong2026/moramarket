@extends('layouts.workspace')
@section('title','Notifications')
@section('subtitle','Updates from real orders and support conversations. Only your own notifications are shown.')
@section('page_actions')
<form method="POST" action="{{ route('account.notifications.read-all') }}">@csrf
<button class="btn btn-outline" type="submit">Mark all as read</button>
</form>
@endsection
@section('content')
<div class="table-panel">
  <div class="panel-pad" style="display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap">
    <div class="muted">Showing {{ $notifications->total() }} notification(s) · 10 per page</div>
    <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap">
      <a class="btn {{ $filter==='all'?'btn-primary':'btn-outline' }}" href="{{ route('account.notifications.index',['status'=>'all']) }}">All</a>
      <a class="btn {{ $filter==='unread'?'btn-primary':'btn-outline' }}" href="{{ route('account.notifications.index',['status'=>'unread']) }}">Unread</a>
      <a class="btn {{ $filter==='read'?'btn-primary':'btn-outline' }}" href="{{ route('account.notifications.index',['status'=>'read']) }}">Read</a>
    </div>
  </div>
  <div class="table-scroll">
    <table class="data-table">
      <thead><tr><th>No</th><th>Notification</th><th>Received</th><th>Status</th><th>Action</th></tr></thead>
      <tbody>
        @forelse($notifications as $notice)
          <tr @class(['notification-unread'=>is_null($notice->read_at)])>
            <td>{{ $notifications->firstItem()+$loop->index }}</td>
            <td><strong>{{ $notice->data['heading'] ?? 'Marketplace update' }}</strong><div class="muted">{{ $notice->data['message'] ?? '' }}</div></td>
            <td>{{ $notice->created_at->format('Y-m-d H:i') }}</td>
            <td><span class="pill">{{ $notice->read_at?'Read':'Unread' }}</span></td>
            <td><x-ui.action-trigger menu="notification-menu-{{ $notice->id }}" label="Notification actions" /></td>
          </tr>
        @empty
          <tr><td colspan="5" class="muted" style="text-align:center;padding:32px">No notifications match your filter.</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>
  @include('partials.table-footer',['paginator'=>$notifications])
</div>
@foreach($notifications as $notice)
  <x-ui.action-menu id="notification-menu-{{ $notice->id }}">
    @if(isset($notice->data['path']) && str_starts_with($notice->data['path'],'/') && !str_starts_with($notice->data['path'],'//'))
      <a href="{{ $notice->data['path'] }}">View related item</a>
    @endif
    @if(is_null($notice->read_at))
      <form action="{{ route('account.notifications.read',$notice->id) }}" method="POST">@csrf<button type="submit">Mark as read</button></form>
    @endif
  </x-ui.action-menu>
@endforeach
@endsection
