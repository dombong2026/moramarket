@extends('layouts.workspace')
@section('title','Media Library')
@section('subtitle','Manage uploaded images, edit their information, and reuse them across product listings.')
@section('page_actions')
<button class="btn btn-primary" type="button" data-open-modal="media-upload">@include('partials.icon',['name'=>'plus']) Upload Image</button>
@endsection
@section('content')
<div class="table-panel">
@include('partials.table-tools',['statuses'=>['jpeg'=>'JPEG','png'=>'PNG','webp'=>'WebP','mp4'=>'MP4','webm'=>'WebM']])
<div class="table-scroll"><table class="data-table"><thead><tr><th>No</th><th>Image</th><th>File</th>@if($workspace==='admin')<th>Uploaded By</th>@endif<th>Type</th><th>Size</th><th>Product Uses</th><th>Uploaded</th><th>Actions</th></tr></thead><tbody>
@forelse($assets as $asset)
<tr>
<td>{{ $assets->firstItem()+$loop->index }}</td>
<td>@if(str_starts_with($asset->mime_type,'video/'))<span class="video-thumb">VIDEO</span>@else<img src="{{ route('media.preview',$asset) }}" alt="{{ $asset->alt_text ?? $asset->original_name }}" loading="lazy" style="width:55px;height:48px;border-radius:9px;object-fit:cover;background:var(--surface-alt)">@endif</td>
<td><strong>{{ $asset->original_name }}</strong><small class="small">{{ $asset->alt_text ?: 'No alternative text' }}</small></td>
@if($workspace==='admin')<td>{{ $asset->user->name }}<span class="small">{{ $asset->user->email }}</span></td>@endif
<td>{{ strtoupper(str_replace(['image/','video/'],'',$asset->mime_type)) }}</td>
<td>{{ number_format($asset->size_bytes / 1024,1) }} KB</td>
<td>{{ $asset->products_count }}</td><td>{{ $asset->created_at->format('Y-m-d') }}</td>
<td><x-ui.action-trigger menu="asset-menu-{{ $asset->id }}" label="Image actions" /></td>
</tr>
@empty<tr><td colspan="{{ $workspace==='admin'?9:8 }}" style="text-align:center;padding:35px" class="muted">No images found. Upload an image to get started.</td></tr>@endforelse
</tbody></table></div>
@include('partials.table-footer',['paginator'=>$assets])
</div>
<dialog id="media-upload" class="modal"><div class="modal-header"><h2>Upload Image</h2><button type="button" class="modal-close" data-close-modal>@include('partials.icon',['name'=>'close'])</button></div>
<form method="POST" enctype="multipart/form-data" action="{{ $workspace==='admin' ? route('admin.media.upload') : route('vendor.media.upload') }}">@csrf
<div class="modal-body"><div class="field-block"><label class="field-label" for="media-file">Image or video file</label><input class="field" id="media-file" name="file" type="file" accept="image/jpeg,image/png,image/webp,video/mp4,video/webm" data-file-preview="media-upload-preview" required><p class="native-note">JPEG, PNG, WebP images up to 5 MB; MP4 or WebM video up to 30 MB. Files are stored outside direct public downloads.</p></div><img id="media-upload-preview" class="media-picker-preview" alt="New image preview" hidden><div class="field-block"><label class="field-label" for="media-alt">Alternative text (optional)</label><input class="field" name="alt_text" id="media-alt" maxlength="160" placeholder="Briefly describe the image"></div></div>
<div class="modal-footer"><button type="button" class="btn btn-outline" data-close-modal>Cancel</button><button class="btn btn-primary" type="submit">Upload Image</button></div></form></dialog>
@foreach($assets as $asset)
<div id="asset-menu-{{ $asset->id }}" class="action-popover" popover="auto"><button type="button" data-open-modal="asset-preview-{{ $asset->id }}">View image</button><button type="button" data-open-modal="asset-edit-{{ $asset->id }}">@include('partials.icon',['name'=>'edit','size'=>15]) Edit details</button><button type="button" class="danger" data-open-modal="asset-delete-{{ $asset->id }}">@include('partials.icon',['name'=>'trash','size'=>15]) Delete image</button></div>
<dialog class="modal modal-lg" id="asset-preview-{{ $asset->id }}"><div class="modal-header"><h2>{{ $asset->original_name }}</h2><button type="button" class="modal-close" data-close-modal>@include('partials.icon',['name'=>'close'])</button></div><div class="modal-body">@if(str_starts_with($asset->mime_type,'video/'))<video controls playsinline preload="metadata" style="max-width:100%;max-height:65vh;border-radius:12px"><source src="{{ route('media.preview',$asset) }}" type="{{ $asset->mime_type }}"></video>@else<img style="max-width:100%;max-height:65vh;object-fit:contain;margin:auto;border-radius:12px" src="{{ route('media.preview',$asset) }}" alt="{{ $asset->alt_text ?? $asset->original_name }}">@endif<p class="native-note">{{ $asset->alt_text ?: $asset->original_name }}</p></div><div class="modal-footer"><button type="button" class="btn btn-outline" data-close-modal>Close</button></div></dialog>
<dialog class="modal" id="asset-edit-{{ $asset->id }}"><div class="modal-header"><h2>Edit Media Details</h2><button type="button" class="modal-close" data-close-modal>@include('partials.icon',['name'=>'close'])</button></div>
<form method="POST" action="{{ $workspace==='admin' ? route('admin.media.update',$asset) : route('vendor.media.update',$asset) }}">@csrf @method('PATCH')<div class="modal-body">
<div class="field-block"><label class="field-label" for="file-name-{{ $asset->id }}">Display file name</label><input class="field" name="original_name" id="file-name-{{ $asset->id }}" maxlength="190" required value="{{ $asset->original_name }}"></div>
<div class="field-block"><label class="field-label" for="alt-{{ $asset->id }}">Alternative text</label><input class="field" name="alt_text" id="alt-{{ $asset->id }}" maxlength="160" value="{{ $asset->alt_text }}"></div>
</div><div class="modal-footer"><button type="button" class="btn btn-outline" data-close-modal>Cancel</button><button class="btn btn-primary" type="submit">Save Details</button></div></form></dialog>
<dialog class="modal" id="asset-delete-{{ $asset->id }}"><div class="modal-header"><h2>Delete Image?</h2><button type="button" class="modal-close" data-close-modal>@include('partials.icon',['name'=>'close'])</button></div>
<form method="POST" action="{{ $workspace==='admin' ? route('admin.media.delete',$asset) : route('vendor.media.delete',$asset) }}">@csrf @method('DELETE')<div class="modal-body"><p>Delete <strong>{{ $asset->original_name }}</strong>? If the image is used by a product, deletion will be blocked until its featured media is changed.</p></div><div class="modal-footer"><button type="button" class="btn btn-outline" data-close-modal>Cancel</button><button class="btn btn-danger" type="submit">Delete Image</button></div></form></dialog>
@endforeach
@endsection
