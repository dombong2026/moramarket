@extends('layouts.site')
@section('title',$product->title)
@section('content')
<div class="container page-wrap">
 <div class="muted" style="margin-bottom:22px"><a href="{{ route('locale.home',['locale'=>app()->getLocale()]) }}">Home</a> / <a href="{{ route('locale.category',['locale'=>app()->getLocale(),'category'=>$product->category->slug]) }}">{{ $product->category->name }}</a> / {{ $product->title }}</div>
 <div class="product-detail">
   <div class="product-detail-image">@if($product->image_path)<img src="{{ asset('storage/'.$product->image_path) }}" alt="{{ $product->title }}">@else@include('partials.icon',['name'=>'box','size'=>56])@endif</div>
   <div class="product-detail-info"><span class="pill">{{ ucfirst($product->type) }}</span><h1 style="margin-top:14px">{{ $product->title }}</h1><p class="muted">Sold by {{ $product->vendor->name }}</p><div class="detail-price">{{ number_format((float)$product->price,2) }}</div><div class="details-text">{{ $product->description }}</div>
   @if($product->type==='physical')
      @if($product->stock > 0)<p class="muted" style="margin-top:20px">{{ $product->stock }} in stock</p><form class="detail-form" action="{{ route('cart.add',$product) }}" method="POST">@csrf<div><label class="field-label" for="quantity">Quantity</label><input type="number" name="quantity" id="quantity" class="field" min="1" max="{{ min(99,$product->stock) }}" value="1" required></div><button class="btn btn-primary" type="submit">@include('partials.icon',['name'=>'cart']) Add to Cart</button></form>
      @else<p class="alert" style="margin-top:18px">Currently out of stock.</p>@endif
   @else<p class="alert" style="margin-top:20px">This listing is currently informational. Secure checkout for digital products and services is not yet enabled.</p>@endif
   </div>
 </div>
</div>
@endsection
