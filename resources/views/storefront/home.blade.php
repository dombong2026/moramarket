@extends('layouts.site')
@section('title','Home')
@section('content')
<div class="container page-wrap">
  <section class="hero"><span class="hero-badge">MORAMARKET · MULTI-VENDOR MARKETPLACE</span><h1>Discover something worth sharing.</h1><p>Shop independent sellers, explore everyday essentials, and find new products and services from one marketplace.</p><div class="hero-actions"><a class="btn btn-primary" href="{{ route('locale.catalog',['locale'=>app()->getLocale()]) }}">Explore Marketplace @include('partials.icon',['name'=>'arrow'])</a>@auth<a class="btn btn-light" href="{{ route('account.home') }}">My Account</a>@else<a class="btn btn-light" href="{{ route('register') }}">Create an Account</a>@endauth</div></section>
  <div class="section-heading"><div><h2>Shop by category</h2><p>Browse the market your way.</p></div><a class="text-link" href="{{ route('locale.catalog',['locale'=>app()->getLocale()]) }}">View all</a></div>
  <div class="category-grid">@foreach($categories->take(12) as $category)<a class="category-tile" href="{{ route('locale.category',['locale'=>app()->getLocale(),'category'=>$category->slug]) }}"><span class="category-icon">@include('partials.icon',['name'=>'grid'])</span><span>{{ $category->name }}</span></a>@endforeach</div>
  <div class="section-heading"><div><h2>Latest products</h2><p>New listings from approved vendors.</p></div><a class="text-link" href="{{ route('locale.catalog',['locale'=>app()->getLocale()]) }}">Browse all products</a></div>
  @if($products->isEmpty())<div class="empty-state"><strong>No products published yet</strong>Published listings will appear here after vendors are approved and products are added.</div>
  @else<div class="product-grid">@foreach($products as $product)@include('storefront.partials.product-card',['product'=>$product])@endforeach</div><div style="margin-top:23px">{{ $products->links() }}</div>@endif
</div>
@endsection
