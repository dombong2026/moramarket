@extends('layouts.site')
@section('title','Checkout')
@section('content')
<div class="container page-wrap">
<div class="section-heading"><div><h1 style="font-size:30px;font-weight:820">Checkout</h1><p>Confirm delivery details for your physical products.</p></div></div>
<div class="checkout-grid">
 <form class="panel panel-pad" method="POST" action="{{ route('checkout.place') }}">@csrf
  <h2 style="font-weight:780;font-size:18px;margin-bottom:18px">Delivery details</h2>
  <div class="field-block"><label class="field-label" for="customer-name">Full name</label><input class="field" id="customer-name" name="customer_name" value="{{ old('customer_name',auth()->user()->name) }}" maxlength="160" required></div>
  <div class="field-block"><label class="field-label" for="customer-email">Email</label><input class="field" type="email" id="customer-email" name="customer_email" value="{{ old('customer_email',auth()->user()->email) }}" required></div>
  <div class="field-block"><label class="field-label" for="shipping-address">Complete delivery address</label><textarea class="field" id="shipping-address" name="shipping_address" rows="4" minlength="10" maxlength="2000" required>{{ old('shipping_address') }}</textarea></div>
  <div class="alert" style="margin:18px 0">Payment method: <strong>Cash on Delivery</strong>. No online payment will be charged. Each vendor receives a separate order.</div>
  <button class="btn btn-primary" type="submit">Place Order @include('partials.icon',['name'=>'arrow'])</button>
 </form>
 <div class="panel order-summary"><h2 style="font-size:18px;font-weight:780;margin-bottom:20px">Order summary</h2>
 @foreach($items as $line)<div style="display:flex;justify-content:space-between;gap:15px;padding:9px 0"><span>{{ $line['product']->title }} <span class="muted">× {{ $line['quantity'] }}</span></span><span>{{ number_format($line['lineCents']/100,2) }}</span></div>@endforeach
 <div class="total-row"><span>Total</span><strong>{{ number_format($total,2) }}</strong></div>
 <p class="native-note">Shipping fees and taxes are not calculated in this initial checkout. Vendor order terms must be confirmed before operating commercially.</p>
 </div>
</div>
</div>
@endsection
