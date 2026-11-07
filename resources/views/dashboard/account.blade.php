@extends('layouts.workspace')
@section('title','My Account')
@section('subtitle','Manage your profile, orders, and marketplace participation.')
@section('page_actions')
<button type="button" class="btn btn-primary" data-open-modal="profile-edit">@include('partials.icon',['name'=>'edit']) Edit Profile</button>
@endsection
@section('content')
<div class="metric-grid">
 <div class="metric-card"><small>My orders</small><strong>{{ number_format($ordersCount) }}</strong></div>
 <div class="metric-card"><small>Account type</small><strong style="font-size:23px;text-transform:capitalize">{{ $user->role }}</strong></div>
 <div class="metric-card"><small>Vendor application</small><strong style="font-size:21px;text-transform:capitalize">{{ $user->vendor?->status ?? 'Not applied' }}</strong></div>
</div>
<div class="subsection"><h2>Recent orders</h2><div class="table-panel"><div class="table-scroll"><table class="data-table"><thead><tr><th>Order</th><th>Store</th><th>Total</th><th>Status</th><th>Date</th></tr></thead><tbody>
@forelse($recentOrders as $order)<tr><td>{{ substr($order->number,0,8) }}</td><td>{{ $order->vendor->name }}</td><td>{{ number_format((float)$order->total,2) }}</td><td><span class="pill {{ $order->status }}">{{ $order->status }}</span></td><td>{{ $order->created_at->format('Y-m-d') }}</td></tr>@empty<tr><td colspan="5" class="muted" style="text-align:center;padding:26px">No orders yet.</td></tr>@endforelse</tbody></table></div><div class="table-footer"><span>Your latest orders</span><a href="{{ route('account.orders') }}" class="text-link">View order history</a></div></div></div>
@if(!$user->vendor && !$user->isAdmin())
<div class="subsection"><div class="panel panel-pad" style="display:flex;align-items:center;justify-content:space-between;gap:14px;flex-wrap:wrap"><div><h2>Become a Vendor</h2><p class="muted">Apply to open a store on Moramarket. Applications require administrator approval.</p></div><button class="btn btn-primary" data-open-modal="vendor-apply">Apply to sell</button></div></div>
@elseif($user->vendor && $user->vendor->status==='approved')
<div class="subsection"><div class="panel panel-pad"><h2>My Store</h2><p class="muted">Your store is approved and ready to manage.</p><a href="{{ route('vendor.home') }}" class="btn btn-secondary">Go to Vendor Dashboard</a></div></div>
@elseif($user->vendor)
<div class="subsection"><div class="panel panel-pad"><h2>Vendor application</h2><p>Your application is <span class="pill {{ $user->vendor->status }}">{{ $user->vendor->status }}</span>. Contact the marketplace administrator for changes.</p></div></div>
@endif
<dialog id="profile-edit" class="modal"><div class="modal-header"><h2>Edit Profile</h2><button class="modal-close" data-close-modal aria-label="Close">@include('partials.icon',['name'=>'close'])</button></div>
<form method="POST" action="{{ route('account.profile.update') }}">@csrf @method('PATCH')
<div class="modal-body"><div class="field-block"><label class="field-label" for="profile-name">Name</label><input class="field" id="profile-name" name="name" value="{{ $user->name }}" required maxlength="160"></div><div class="field-block"><label class="field-label" for="profile-email">Email</label><input class="field" id="profile-email" name="email" type="email" value="{{ $user->email }}" required></div><div class="field-block"><label class="field-label" for="current-password">Current password (required to change password)</label><input class="field" name="current_password" type="password" id="current-password"></div><div class="field-block"><label class="field-label" for="new-password">New password (optional)</label><input class="field" name="password" type="password" id="new-password" minlength="10"></div><div class="field-block"><label class="field-label" for="new-password-confirm">Confirm new password</label><input class="field" name="password_confirmation" id="new-password-confirm" type="password"></div></div>
<div class="modal-footer"><button type="button" class="btn btn-outline" data-close-modal>Cancel</button><button type="submit" class="btn btn-primary">Save Changes</button></div></form></dialog>
@if(!$user->vendor && !$user->isAdmin())<dialog id="vendor-apply" class="modal"><div class="modal-header"><h2>Vendor Application</h2><button class="modal-close" data-close-modal aria-label="Close">@include('partials.icon',['name'=>'close'])</button></div><form method="POST" action="{{ route('account.vendor.apply') }}">@csrf<div class="modal-body"><div class="field-block"><label for="store-name" class="field-label">Store name</label><input class="field" id="store-name" name="store_name" minlength="3" maxlength="160" required></div><div class="field-block"><label for="store-desc" class="field-label">About your store</label><textarea class="field" id="store-desc" name="description" rows="4" maxlength="2000"></textarea></div><p class="native-note">An administrator will review your application. You can manage products after approval.</p></div><div class="modal-footer"><button type="button" class="btn btn-outline" data-close-modal>Cancel</button><button type="submit" class="btn btn-primary">Submit Application</button></div></form></dialog>@endif
@endsection
