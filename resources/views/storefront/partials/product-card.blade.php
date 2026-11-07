<article class="product-card">
 <a class="product-photo" href="{{ route('locale.product',['locale'=>app()->getLocale(),'product'=>$product->slug]) }}">
 @if($product->image_path)<img src="{{ route('media.product',$product) }}" alt="{{ $product->title }}" loading="lazy">@else
 <span class="muted">@include('partials.icon',['name'=>'box','size'=>37])</span>@endif
 </a>
 <div class="product-card-content"><div class="product-type">{{ ucfirst($product->type) }} · {{ $product->category->name }}</div>
 <h3><a href="{{ route('locale.product',['locale'=>app()->getLocale(),'product'=>$product->slug]) }}">{{ $product->title }}</a></h3>
 <p class="product-vendor">{{ $product->vendor->name }}</p><div class="product-price">{{ number_format((float)$product->price,2) }}</div></div>
</article>
