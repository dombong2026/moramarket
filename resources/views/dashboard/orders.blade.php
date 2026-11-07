@extends('layouts.workspace')
@section('title',$workspace==='admin'?'Order Management':($workspace==='vendor'?'Store Orders':'My Orders'))
@section('subtitle','Review real orders, delivery charges, customer details and fulfillment status.')
@section('page_actions')
@if($workspace==='vendor')<a class="btn btn-primary" href="{{ route('vendor.shipping.edit') }}">Delivery Settings</a>@elseif($workspace==='admin')<a class="btn btn-primary" href="{{ route('admin.home') }}">Dashboard</a>@else<a class="btn btn-primary" href="{{ route('locale.catalog',['locale'=>app()->getLocale()]) }}">Continue Shopping</a>@endif
@endsection
@section('content')
<div class="table-panel">
@include('partials.table-tools',['statuses'=>['pending'=>'Pending','processing'=>'Processing','shipped'=>'Shipped','completed'=>'Completed','cancelled'=>'Cancelled']])
<div class="table-scroll"><table class="data-table"><thead><tr><th>No</th><th>Order</th><th>Customer</th><th>Vendor</th><th>Subtotal</th><th>Shipping</th><th>Total</th><th>Payment</th><th>Delivery Status</th><th>Date</th><th>Actions</th></tr></thead><tbody>
@forelse($orders as $order)<tr>
<td>{{ $orders->firstItem()+$loop->index }}</td><td><strong>{{ substr($order->number,0,8) }}</strong></td>
<td>{{ $order->customer_name }}</td><td>{{ $order->vendor->name }}</td><td>{{ number_format((float)($order->subtotal?:$order->total),2) }}</td><td>{{ number_format((float)$order->shipping_fee,2) }}</td><td>{{ number_format((float)$order->total,2) }}</td><td>{{ strtoupper($order->payment_method) }}<span class="small">{{ ucfirst($order->payment_status) }}</span></td><td><span class="pill {{ $order->status }}">{{ $order->status }}</span></td><td>{{ $order->created_at->format('Y-m-d') }}</td><td><x-ui.action-trigger menu="order-menu-{{ $order->id }}" label="Order actions" /></td>
</tr>@empty<tr><td colspan="11" style="padding:35px;text-align:center" class="muted">No orders match your filters.</td></tr>@endforelse
</tbody></table></div>
@include('partials.table-footer',['paginator'=>$orders])
</div>
@foreach($orders as $order)
<x-ui.action-menu id="order-menu-{{ $order->id }}"><button type="button" data-open-modal="order-view-{{ $order->id }}">View order</button>@if($workspace==='vendor' && !in_array($order->status,['completed','cancelled']))<button type="button" data-open-modal="order-status-{{ $order->id }}">Update delivery</button>@endif</x-ui.action-menu>
<x-ui.modal-frame id="order-view-{{ $order->id }}" size="large"><div class="modal-header"><h2>Order {{ $order->number }}</h2><button type="button" class="modal-close" data-close-modal>@include('partials.icon',['name'=>'close'])</button></div>
<div class="modal-body"><div class="form-grid"><div><strong>Customer</strong><p>{{ $order->customer_name }}<br>{{ $order->customer_email }}</p></div><div><strong>Vendor</strong><p>{{ $order->vendor->name }}</p></div>
<div class="full"><strong>Delivery address</strong><p style="white-space:pre-wrap">{{ $order->shipping_address }}</p></div>
@if($order->shipping_carrier || $order->tracking_number)<div class="full"><strong>Delivery tracking</strong><p>{{ $order->shipping_carrier }} · {{ $order->tracking_number }}</p></div>@endif
<div><strong>Shipped</strong><p>{{ $order->shipped_at?->format('Y-m-d H:i')??'Not yet' }}</p></div><div><strong>Completed</strong><p>{{ $order->delivered_at?->format('Y-m-d H:i')??'Not yet' }}</p></div></div>
<h3 style="font-weight:780;margin:20px 0 8px">Items</h3><div class="table-scroll"><table class="data-table" style="min-width:400px"><thead><tr><th>Product</th><th>Quantity</th><th>Unit price</th><th>Total</th></tr></thead><tbody>@foreach($order->items as $item)<tr><td>{{ $item->title_snapshot }}</td><td>{{ $item->quantity }}</td><td>{{ number_format((float)$item->unit_price,2) }}</td><td>{{ number_format((float)$item->line_total,2) }}</td></tr>@endforeach</tbody></table></div>
<p style="display:flex;justify-content:space-between;margin-top:16px"><span>Shipping fee</span><strong>{{ number_format((float)$order->shipping_fee,2) }}</strong></p>
<div class="total-row">Order total <strong>{{ number_format((float)$order->total,2) }}</strong></div></div>
<div class="modal-footer"><button type="button" class="btn btn-outline" data-close-modal>Close</button></div></x-ui.modal-frame>
@if($workspace==='vendor' && !in_array($order->status,['completed','cancelled']))
<x-ui.modal-frame id="order-status-{{ $order->id }}"><div class="modal-header"><h2>Update Delivery Status</h2><button type="button" class="modal-close" data-close-modal>@include('partials.icon',['name'=>'close'])</button></div>
<form method="POST" action="{{ route('vendor.orders.update',$order) }}">@csrf @method('PATCH')
<div class="modal-body"><p class="muted" style="margin-bottom:14px">Order {{ substr($order->number,0,8) }} · Current status: {{ ucfirst($order->status) }}</p>
<label class="field-label" for="order-new-status-{{ $order->id }}">Next step</label>
<select class="field" id="order-new-status-{{ $order->id }}" name="status" required>
@if($order->status==='pending')<option value="processing">Start Processing</option><option value="cancelled">Cancel and restore stock</option>
@elseif($order->status==='processing')<option value="shipped">Mark as Shipped</option><option value="cancelled">Cancel and restore stock</option>
@elseif($order->status==='shipped')<option value="completed">Mark as Completed</option>@endif</select>
@if($order->status==='processing')
<div class="field-block" style="margin-top:16px"><label class="field-label" for="carrier-{{ $order->id }}">Delivery company (optional)</label><input class="field" id="carrier-{{ $order->id }}" name="shipping_carrier" maxlength="100"></div>
<div class="field-block"><label class="field-label" for="tracking-{{ $order->id }}">Tracking reference (optional)</label><input class="field" id="tracking-{{ $order->id }}" name="tracking_number" maxlength="120"></div>
@endif
</div><div class="modal-footer"><button type="button" class="btn btn-outline" data-close-modal>Cancel</button><button type="submit" class="btn btn-primary">Update Delivery</button></div></form></x-ui.modal-frame>
@endif
@endforeach
@endsection
