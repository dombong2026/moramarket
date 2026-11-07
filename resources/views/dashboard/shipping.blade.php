@extends('layouts.workspace')
@section('title','Delivery Settings')
@section('subtitle','Set a clear flat delivery fee, free-delivery threshold and dispatch estimate for your store.')
@section('page_actions')<a class="btn btn-primary" href="{{ route('vendor.orders') }}">View Store Orders</a>@endsection
@section('content')
<div class="panel panel-pad" style="max-width:730px">
<form method="POST" action="{{ route('vendor.shipping.update') }}">@csrf @method('PATCH')
<div class="field-block"><label class="field-label" for="shipping-enabled">Accept delivery orders</label><select class="field" name="enabled" id="shipping-enabled"><option value="1" @selected(($profile?->enabled??true))>Yes</option><option value="0" @selected($profile && !$profile->enabled)>No, pause new checkout</option></select></div>
<div class="form-grid">
<div><label class="field-label" for="shipping-flat">Flat shipping fee (same currency as products)</label><input class="field" type="number" min="0" step="0.01" name="flat_rate" id="shipping-flat" value="{{ $profile?->flat_rate??'0.00' }}" required></div>
<div><label class="field-label" for="shipping-free">Free delivery above this subtotal (optional)</label><input class="field" type="number" min="0" step="0.01" name="free_above" id="shipping-free" value="{{ $profile?->free_above }}"></div>
<div class="full"><label class="field-label" for="shipping-days">Estimated delivery days</label><input class="field" name="estimated_days" id="shipping-days" type="number" min="1" max="30" value="{{ $profile?->estimated_days??3 }}" required></div>
<div class="full"><label class="field-label" for="shipping-note">Shipping notes for your team (optional)</label><textarea class="field" name="policy_note" id="shipping-note" maxlength="300" rows="3">{{ $profile?->policy_note }}</textarea></div>
</div>
<p class="native-note">By default, a new seller offers free delivery until a rate is saved. Checkout shows delivery charges separately for each seller. Cross-border shipping, tax, courier integrations and complex shipping zones are not yet enabled.</p>
<div style="display:flex;justify-content:flex-end;margin-top:22px"><button class="btn btn-primary" type="submit">Save Delivery Settings</button></div>
</form></div>
@endsection
