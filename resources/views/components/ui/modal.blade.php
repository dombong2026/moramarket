@props([
    'id',
    'title',
    'size' => 'normal',
    'submitLabel' => 'Save Changes',
    'danger' => false,
])
<dialog id="{{ $id }}" @class(['modal', 'modal-lg' => $size==='large']) aria-labelledby="{{ $id }}-heading">
  <div class="modal-header">
    <h2 id="{{ $id }}-heading">{{ $title }}</h2>
    <button type="button" class="modal-close" data-close-modal aria-label="Close {{ $title }}">
      @include('partials.icon', ['name'=>'close'])
    </button>
  </div>
  {{ $slot }}
</dialog>
