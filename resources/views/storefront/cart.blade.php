@extends('layouts.site')
@section('title','Shopping Cart')
@section('content')
<div class="container page-wrap"><div class="section-heading"><div><h1 style="font-size:30px;font-weight:820">Shopping Cart</h1><p>Review your items before checkout.</p></div><a href="{{ route('locale.catalog',['locale'=>app()->getLocale()]) }}" class="btn btn-outline">Continue Shopping</a></div>
@if($items->isEmpty())<div class="empty-state"><strong>Your cart is empty</strong>Add a product to continue.</div>
@else
<div class="cart-list">
@foreach($items as $line)
@php($product=$line['product'])
<div class="panel cart-item"><div class="cart-item-image">@if($product->image_path)<img alt="{{ $product->title }}" src="{{ route('media.product',$product) }}">@else@include('partials.icon',['name'=>'box'])@endif</div>
 <div><a style="font-weight:750" href="{{ route('locale.product',['locale'=>app()->getLocale(),'product'=>$product->slug]) }}">{{ $product->title }}</a><p class="muted">{{ $product->vendor->name }} · {{ number_format((float)$product->price,2) }} each</p></div>
 <strong>{{ number_format($line['lineCents']/100,2) }}</strong>
 <div class="cart-item-actions"><form action="{{ route('cart.update',$product) }}" method="POST" style="display:flex;gap:8px;align-items:center">@csrf @method('PATCH')<label class="sr-only" for="cart-qty-{{ $product->id }}">Quantity</label><input class="field" id="cart-qty-{{ $product->id }}" name="quantity" type="number" min="0" max="{{ min(99,$product->stock) }}" value="{{ $line['quantity'] }}" required><button type="submit" class="btn btn-outline btn-sm">Update</button></form><form action="{{ route('cart.remove',$product) }}" method="POST">@csrf @method('DELETE')<button class="btn btn-ghost btn-sm" type="submit">Remove</button></form></div>
</div>
@endforeach
</div><div class="panel panel-pad" style="margin-top:20px;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:15px"><div class="muted">Total <strong style="display:block;color:var(--text);font-size:26px">{{ number_format($total,2) }}</strong></div><a class="btn btn-primary" href="{{ route('checkout.form') }}">Checkout @include('partials.icon',['name'=>'arrow'])</a></div>
@endif
</div>
@endsection
