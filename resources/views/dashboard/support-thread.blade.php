@extends('layouts.workspace')
@section('title','Support Conversation')
@section('subtitle',$ticket->subject)
@section('page_actions')
<a class="btn btn-outline" href="{{ $workspace==='admin'?route('admin.support.index'):route('account.support.index') }}">Back to Tickets</a>
@if($workspace==='admin')<button class="btn btn-primary" type="button" data-open-modal="ticket-status">Change Status</button>@endif
@endsection
@section('content')
<div class="panel panel-pad" style="max-width:900px">
<div style="display:flex;justify-content:space-between;align-items:start;gap:15px;flex-wrap:wrap"><div><h2 style="font-size:20px;font-weight:750">{{ $ticket->subject }}</h2><p class="muted">Requested by {{ $ticket->user->name }} · {{ $ticket->created_at->format('d M Y') }}</p></div><span class="pill {{ $ticket->status }}">{{ ucfirst($ticket->status) }}</span></div>
<div class="conversation-thread">
@foreach($ticket->messages as $message)<div class="conversation-message"><div style="display:flex;justify-content:space-between;gap:12px;flex-wrap:wrap"><strong>{{ $message->author->name }}</strong><small class="muted">{{ $message->created_at->format('d M Y H:i') }}</small></div><p style="white-space:pre-wrap;margin-top:9px">{{ $message->body }}</p></div>@endforeach
</div>
@if($ticket->status!=='closed')<form method="POST" action="{{ $workspace==='admin'?route('admin.support.reply',$ticket):route('account.support.reply',$ticket) }}">@csrf
<div class="field-block"><label for="ticket-reply" class="field-label">Reply</label><textarea class="field" rows="5" maxlength="10000" name="message" id="ticket-reply" required></textarea></div>
<div style="display:flex;justify-content:flex-end"><button class="btn btn-primary" type="submit">Send Reply</button></div></form>
@else<p class="alert">This conversation is closed. An administrator can reopen it.</p>@endif
</div>
@if($workspace==='admin')
<dialog class="modal" id="ticket-status"><div class="modal-header"><h2>Change Ticket Status</h2><button type="button" class="modal-close" data-close-modal>@include('partials.icon',['name'=>'close'])</button></div>
<form method="POST" action="{{ route('admin.support.status',$ticket) }}">@csrf @method('PATCH')
<div class="modal-body"><div class="field-block"><label class="field-label" for="ticket-status-choice">Status</label><select class="field" name="status" id="ticket-status-choice"><option value="open" @selected($ticket->status==='open')>Open</option><option value="waiting" @selected($ticket->status==='waiting')>Waiting for customer</option><option value="closed" @selected($ticket->status==='closed')>Closed</option></select></div></div>
<div class="modal-footer"><button class="btn btn-outline" type="button" data-close-modal>Cancel</button><button class="btn btn-primary" type="submit">Update Status</button></div></form></dialog>
@endif
@endsection
