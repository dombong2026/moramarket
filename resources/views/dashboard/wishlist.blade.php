@extends('layouts.workspace')
@section('title','Saved Products')
@section('subtitle','Keep a private list of products to explore later. Saving a listing does not reserve stock or place an order.')
@section('page_actions')
<a href="{{ route('locale.catalog',['locale'=>app()->getLocale()]) }}" class="btn btn-primary">Browse Marketplace</a>
@endsection
@section('content')
<div class="panel panel-pad">
  <div style="display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap;margin-bottom:20px">
    <h2 style="font-size:19px;font-weight:800">My Wishlist</h2>
    <span class="muted">{{ $products->total() }} saved {{ $products->total() === 1 ? 'listing' : 'listings' }}</span>
  </div>
  @if($products->isEmpty())
    <div class="empty-state">
      <strong>No saved products yet</strong>
      Browse the marketplace and select “Save to Wishlist” on a product you like.
    </div>
  @else
    <div class="product-grid">
      @foreach($products as $product)
        @php($available = $product->status === 'published' && $product->vendor?->status === 'approved' && (bool) $product->category?->is_active)
        <div style="min-width:0">
          @if($available)
            @include('storefront.partials.product-card',['product'=>$product])
          @else
            <div class="panel panel-pad" style="min-height:180px">
              <span class="pill">Unavailable</span>
              <h3 style="font-size:17px;font-weight:750;margin-top:12px">Saved product unavailable</h3>
              <p class="muted">This listing is no longer available to browse. You can remove it from your wishlist.</p>
            </div>
          @endif
          <form method="POST" action="{{ route('account.wishlist.destroy',$product) }}" style="margin-top:9px">
            @csrf @method('DELETE')
            <button type="submit" class="btn btn-outline btn-sm">Remove from Wishlist</button>
          </form>
        </div>
      @endforeach
    </div>
    <div style="margin-top:24px">{{ $products->links() }}</div>
  @endif
</div>
@endsection
