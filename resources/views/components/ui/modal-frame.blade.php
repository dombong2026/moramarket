@props(['id', 'size' => 'normal'])
<dialog id="{{ $id }}" {{ $attributes->class(['modal', 'modal-lg' => $size === 'large']) }}>
  {{ $slot }}
</dialog>
