@extends('layouts.workspace')
@section('title','Overview')
@section('subtitle',$workspace==='admin' ? 'Marketplace activity and management at a glance.' : 'Manage your store and fulfill customer orders.')
@section('page_actions')
 @if($workspace==='admin')<a class="btn btn-primary" href="{{ route('admin.vendors') }}">Review Vendors @include('partials.icon',['name'=>'arrow'])</a>
 @else<a class="btn btn-primary" href="{{ route('vendor.products') }}">Manage Products @include('partials.icon',['name'=>'arrow'])</a>@endif
@endsection
@section('content')
<div class="metric-grid">@foreach($metrics as $label=>$value)<div class="metric-card"><small>{{ $label }}</small><strong>{{ number_format($value) }}</strong></div>@endforeach</div>
<div class="subsection"><h2>Recent orders</h2>
<div class="table-panel"><div class="table-scroll"><table class="data-table"><thead><tr><th>Order</th><th>Customer</th><th>Vendor</th><th>Total</th><th>Status</th><th>Created</th></tr></thead><tbody>
@forelse($recentOrders as $order)<tr><td><strong>{{ substr($order->number,0,8) }}</strong></td><td>{{ $order->user->name }}</td><td>{{ $order->vendor->name }}</td><td>{{ number_format((float)$order->total,2) }}</td><td><span class="pill {{ $order->status }}">{{ $order->status }}</span></td><td>{{ $order->created_at->format('Y-m-d') }}</td></tr>
@empty<tr><td colspan="6" class="muted" style="text-align:center;padding:30px">No orders yet.</td></tr>@endforelse
</tbody></table></div><div class="table-footer"><span>Latest 5 orders</span><a href="{{ $workspace==='admin' ? route('admin.orders') : route('vendor.orders') }}" class="text-link">View all orders</a></div></div></div>
@endsection
