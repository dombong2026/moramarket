@extends('layouts.workspace')
@section('title',$workspace==='admin'?'Support Center':'My Support Tickets')
@section('subtitle',$workspace==='admin'?'Review customer requests and manage conversations.':'Contact the support team and review responses.')
@section('page_actions')
@if($workspace==='account')<button class="btn btn-primary" type="button" data-open-modal="ticket-create">@include('partials.icon',['name'=>'plus']) New Ticket</button>@else<a class="btn btn-primary" href="{{ route('admin.knowledge.index') }}">Manage Knowledge Base</a>@endif
@endsection
@section('content')
<div class="table-panel">@include('partials.table-tools',['statuses'=>['open'=>'Open','waiting'=>'Waiting','closed'=>'Closed']])
<div class="table-scroll"><table class="data-table"><thead><tr><th>No</th><th>Subject</th>@if($workspace==='admin')<th>Customer</th>@endif<th>Status</th><th>Updated</th><th>Action</th></tr></thead><tbody>
@forelse($tickets as $ticket)<tr><td>{{ $tickets->firstItem()+$loop->index }}</td><td><strong>{{ $ticket->subject }}</strong></td>@if($workspace==='admin')<td>{{ $ticket->user->name }}</td>@endif<td><span class="pill {{ $ticket->status }}">{{ $ticket->status }}</span></td><td>{{ $ticket->updated_at->format('Y-m-d') }}</td><td><x-ui.action-trigger menu="ticket-menu-{{ $ticket->id }}" label="Row actions" /></td></tr>@empty<tr><td colspan="{{ $workspace==='admin'?6:5 }}" class="muted" style="text-align:center;padding:28px">No tickets found.</td></tr>@endforelse
</tbody></table></div>@include('partials.table-footer',['paginator'=>$tickets])</div>
@if($workspace==='account')
<x-ui.modal-frame id="ticket-create"><div class="modal-header"><h2>Contact Support</h2><button type="button" class="modal-close" data-close-modal>@include('partials.icon',['name'=>'close'])</button></div>
<form method="POST" action="{{ route('account.support.store') }}">@csrf
<div class="modal-body"><div class="field-block"><label class="field-label" for="ticket-subject">Subject</label><input class="field" name="subject" id="ticket-subject" minlength="5" maxlength="190" required></div>
<div class="field-block"><label class="field-label" for="ticket-message">Your message</label><textarea class="field" name="message" id="ticket-message" rows="7" minlength="10" maxlength="10000" required></textarea></div></div>
<div class="modal-footer"><button class="btn btn-outline" type="button" data-close-modal>Cancel</button><button class="btn btn-primary" type="submit">Submit Ticket</button></div></form></x-ui.modal-frame>@endif
@foreach($tickets as $ticket)
<x-ui.action-menu id="ticket-menu-{{ $ticket->id }}">
<a href="{{ $workspace==='admin'?route('admin.support.show',$ticket):route('account.support.show',$ticket) }}">View conversation</a>
</x-ui.action-menu>
@endforeach
@endsection
