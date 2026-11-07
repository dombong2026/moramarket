@extends('layouts.workspace')
@section('title','Product Manager')
@section('subtitle','Create, edit, publish, and maintain your marketplace listings.')
@section('page_actions')<button type="button" class="btn btn-primary" data-open-modal="product-create">@include('partials.icon',['name'=>'plus']) Add Product</button>@endsection
@section('content')
<div class="table-panel">
@include('partials.table-tools',['statuses'=>['draft'=>'Draft','published'=>'Published']])
<div class="table-scroll"><table class="data-table"><thead><tr><th>No</th><th>Product</th><th>Category</th><th>Type</th><th>Price</th><th>Stock</th><th>Status</th><th>Updated</th><th>Actions</th></tr></thead><tbody>
@forelse($products as $product)<tr>
<td>{{ $products->firstItem()+$loop->index }}</td>
<td><div style="display:flex;align-items:center;gap:10px"><span class="cart-item-image" style="width:40px;height:40px">@if($product->image_path)<img src="{{ route('media.product',$product) }}" alt="">@else@include('partials.icon',['name'=>'box','size'=>19])@endif</span><strong>{{ $product->title }}</strong></div></td>
<td>{{ $product->category->name }}</td><td>{{ ucfirst($product->type) }}</td><td>{{ number_format((float)$product->price,2) }}</td><td>{{ $product->stock }}</td><td><span class="pill {{ $product->status }}">{{ $product->status }}</span></td><td>{{ $product->updated_at->format('Y-m-d') }}</td>
<td><button type="button" class="action-trigger" data-menu-id="product-menu-{{ $product->id }}" aria-label="Product actions">⋮</button></td>
</tr>@empty<tr><td colspan="9" style="padding:35px;text-align:center" class="muted">No products match your filters. Add your first product using the action above.</td></tr>@endforelse
</tbody></table></div>
@include('partials.table-footer',['paginator'=>$products])
</div>
<dialog id="product-create" class="modal modal-lg"><div class="modal-header"><h2>Add Product</h2><button type="button" class="modal-close" data-close-modal>@include('partials.icon',['name'=>'close'])</button></div>
<form method="POST" action="{{ route('vendor.products.store') }}" enctype="multipart/form-data">@csrf
<div class="modal-body"><div class="form-grid">
<div class="full"><label for="product-create-title" class="field-label">Product name</label><input class="field" id="product-create-title" name="title" maxlength="190" minlength="3" required></div>
<div><label for="product-create-category" class="field-label">Category</label><select class="field" id="product-create-category" name="category_id" required>@foreach($categories as $category)<option value="{{ $category->id }}">{{ $category->name }}</option>@endforeach</select></div>
<div><label for="product-create-type" class="field-label">Listing type</label><select class="field" id="product-create-type" name="type" required><option value="physical">Physical Product</option><option value="digital">Digital Product (listing only)</option><option value="service">Service (listing only)</option></select></div>
<div><label for="product-create-price" class="field-label">Price</label><input class="field" id="product-create-price" type="number" step="0.01" min="0" name="price" required></div>
<div><label for="product-create-stock" class="field-label">Stock quantity</label><input class="field" id="product-create-stock" type="number" min="0" name="stock" value="0" required></div>
<div><label for="product-create-status" class="field-label">Publish status</label><select class="field" id="product-create-status" name="status"><option value="draft">Draft</option><option value="published">Published</option></select></div>
<div class="full"><label for="product-create-media" class="field-label">Choose image from Media Library</label>
<select class="field" id="product-create-media" name="media_id" data-media-picker="create-media-preview">
<option value="">No existing image selected</option>
@foreach($mediaAssets as $asset)<option value="{{ $asset->id }}" data-preview="{{ route('media.preview',$asset) }}">{{ $asset->original_name }}</option>@endforeach
</select><img id="create-media-preview" class="media-picker-preview" alt="Selected featured image" hidden><p class="native-note">You can also <a class="text-link" href="{{ route('vendor.media') }}">manage your Media Library</a>. Uploading a new file below takes priority.</p></div><div class="full"><label for="product-create-image" class="field-label">Or upload a new featured image (JPG, PNG, WebP, up to 5MB)</label><input class="field" type="file" id="product-create-image" name="image" data-file-preview="create-media-preview" accept="image/jpeg,image/png,image/webp"></div>
<div class="full"><label for="product-create-description" class="field-label">Product description</label><textarea class="field" name="description" id="product-create-description" rows="6" maxlength="20000"></textarea></div>
</div><p class="native-note">Digital products and services can be published as informational listings. Checkout is enabled only for available physical products.</p></div><div class="modal-footer"><button class="btn btn-outline" type="button" data-close-modal>Cancel</button><button class="btn btn-primary" type="submit">Create Product</button></div></form></dialog>
@foreach($products as $product)
<div id="product-menu-{{ $product->id }}" class="action-popover" popover="auto">@if($product->status==='published')<a href="{{ route('locale.product',['locale'=>app()->getLocale(),'product'=>$product->slug]) }}">View listing</a>@endif<button type="button" data-open-modal="product-edit-{{ $product->id }}">@include('partials.icon',['name'=>'edit','size'=>15]) Edit</button><button type="button" class="danger" data-open-modal="product-delete-{{ $product->id }}">@include('partials.icon',['name'=>'trash','size'=>15]) Delete</button></div>
<dialog id="product-edit-{{ $product->id }}" class="modal modal-lg"><div class="modal-header"><h2>Edit Product</h2><button type="button" class="modal-close" data-close-modal>@include('partials.icon',['name'=>'close'])</button></div>
<form method="POST" action="{{ route('vendor.products.update',$product) }}" enctype="multipart/form-data">@csrf @method('PATCH')
<div class="modal-body"><div class="form-grid">
<div class="full"><label class="field-label" for="prod-title-{{ $product->id }}">Product name</label><input class="field" name="title" id="prod-title-{{ $product->id }}" value="{{ $product->title }}" maxlength="190" required></div>
<div><label for="prod-category-{{ $product->id }}" class="field-label">Category</label><select class="field" id="prod-category-{{ $product->id }}" name="category_id" required>@foreach($categories as $category)<option value="{{ $category->id }}" @selected($product->category_id===$category->id)>{{ $category->name }}</option>@endforeach</select></div>
<div><label for="prod-type-{{ $product->id }}" class="field-label">Listing type</label><select class="field" id="prod-type-{{ $product->id }}" name="type">@foreach(['physical'=>'Physical Product','digital'=>'Digital Product','service'=>'Service'] as $type=>$label)<option value="{{ $type }}" @selected($product->type===$type)>{{ $label }}</option>@endforeach</select></div>
<div><label class="field-label" for="prod-price-{{ $product->id }}">Price</label><input class="field" type="number" id="prod-price-{{ $product->id }}" name="price" step="0.01" min="0" value="{{ $product->price }}" required></div>
<div><label for="prod-stock-{{ $product->id }}" class="field-label">Stock</label><input class="field" type="number" id="prod-stock-{{ $product->id }}" name="stock" value="{{ $product->stock }}" min="0" required></div>
<div><label for="prod-status-{{ $product->id }}" class="field-label">Status</label><select class="field" id="prod-status-{{ $product->id }}" name="status"><option value="draft" @selected($product->status==='draft')>Draft</option><option value="published" @selected($product->status==='published')>Published</option></select></div>
<div><label class="field-label" for="prod-image-{{ $product->id }}">Replace image</label><input class="field" type="file" id="prod-image-{{ $product->id }}" name="image" accept="image/jpeg,image/png,image/webp"></div>
@if($product->image_path)<div class="full"><img src="{{ route('media.product',$product) }}" alt="Current featured image for {{ $product->title }}" style="max-height:120px;border-radius:10px"></div>@endif
<div class="full"><label class="field-label" for="prod-desc-{{ $product->id }}">Description</label><textarea class="field" id="prod-desc-{{ $product->id }}" name="description" rows="6">{{ $product->description }}</textarea></div>
</div></div><div class="modal-footer"><button class="btn btn-outline" type="button" data-close-modal>Cancel</button><button class="btn btn-primary" type="submit">Save Changes</button></div></form></dialog>
<dialog id="product-delete-{{ $product->id }}" class="modal"><div class="modal-header"><h2>Delete Product?</h2><button type="button" class="modal-close" data-close-modal>@include('partials.icon',['name'=>'close'])</button></div><form method="POST" action="{{ route('vendor.products.delete',$product) }}">@csrf @method('DELETE')<div class="modal-body">Delete <strong>{{ $product->title }}</strong>? Products that have been ordered cannot be deleted.</div><div class="modal-footer"><button class="btn btn-outline" type="button" data-close-modal>Cancel</button><button class="btn btn-danger" type="submit">Delete Product</button></div></form></dialog>
@endforeach
@endsection
