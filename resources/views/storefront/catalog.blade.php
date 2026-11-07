@extends('layouts.site')
@section('title',isset($selectedCategory) ? $selectedCategory->name : 'Products')
@section('content')
<div class="container page-wrap">
 <div class="section-heading"><div><h1 style="font-size:30px;font-weight:810;letter-spacing:-.03em">{{ isset($selectedCategory) ? $selectedCategory->name : 'Explore the Marketplace' }}</h1><p>Discover listings from approved Moramarket vendors.</p></div></div>
 <form method="GET" action="{{ route('locale.catalog',['locale'=>app()->getLocale()]) }}" class="panel panel-pad" style="margin:18px 0 24px;display:flex;gap:10px;align-items:end;flex-wrap:wrap">
  <div style="min-width:170px;flex:2"><label class="field-label" for="catalog-query">Search products</label><input class="field" id="catalog-query" name="q" maxlength="100" value="{{ request('q') }}" placeholder="Product name"></div>
  <div style="min-width:160px;flex:1"><label class="field-label" for="catalog-category">Category</label><select class="field" name="category" id="catalog-category"><option value="">All categories</option>@foreach($categories as $category)<option value="{{ $category->slug }}" @selected(request('category')===$category->slug|| (isset($selectedCategory) && $selectedCategory->id===$category->id))>{{ $category->name }}</option>@endforeach</select></div>
  <div style="min-width:140px;flex:1"><label class="field-label" for="catalog-type">Type</label><select class="field" name="type" id="catalog-type"><option value="">All types</option>@foreach(['physical'=>'Physical product','digital'=>'Digital product','service'=>'Service'] as $key=>$label)<option value="{{ $key }}" @selected(request('type')===$key)>{{ $label }}</option>@endforeach</select></div>
  <button class="btn btn-primary" type="submit">@include('partials.icon',['name'=>'search']) Search</button><a class="btn btn-outline" href="{{ route('locale.catalog',['locale'=>app()->getLocale()]) }}">Clear</a>
 </form>
 @if($products->isEmpty())<div class="empty-state"><strong>No matching products</strong>Try a different search or category.</div>
 @else<div class="product-grid">@foreach($products as $product)@include('storefront.partials.product-card',['product'=>$product])@endforeach</div><div style="margin-top:25px">{{ $products->links() }}</div>@endif
</div>
@endsection
