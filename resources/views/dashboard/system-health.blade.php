@extends('layouts.workspace')
@section('title','System Health')
@section('subtitle','Read-only checks for the current Moramarket Laravel installation.')
@section('page_actions')<a class="btn btn-primary" href="{{ route('admin.health') }}">Refresh Checks</a>@endsection
@section('content')
<div class="metric-grid" style="margin-bottom:22px">
<div class="metric-card"><small>PHP Version</small><strong style="font-size:24px">{{ $php }}</strong></div>
<div class="metric-card"><small>Laravel Version</small><strong style="font-size:24px">{{ $laravel }}</strong></div>
<div class="metric-card"><small>Checks Passing</small><strong>{{ collect($checks)->filter()->count() }}/{{ count($checks) }}</strong></div>
</div>
<div class="panel panel-pad" style="max-width:900px">
<h2 style="font-size:18px;font-weight:790;margin-bottom:8px">Requirement Status</h2>
<p class="muted">These checks report observed configuration and filesystem conditions. They do not replace independent server hardening or deployment acceptance.</p>
<div class="form-grid" style="margin-top:18px">
@foreach($checks as $title=>$pass)
<div class="panel" style="padding:15px;display:flex;justify-content:space-between;align-items:center;gap:10px"><strong>{{ $title }}</strong><span class="pill {{ $pass ? 'approved':'rejected' }}">{{ $pass ? 'Pass':'Review' }}</span></div>
@endforeach
</div>
</div>
@endsection
